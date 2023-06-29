<?php declare(strict_types = 1);

/**
 * TelevisionApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           18.06.23
 */

namespace FastyBird\Connector\Viera\API;

use Evenement;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Exceptions;
use FastyBird\Connector\Viera\Helpers;
use FastyBird\Connector\Viera\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use React\EventLoop;
use React\Http;
use React\Promise;
use React\Socket;
use React\Socket\Connector;
use RuntimeException;
use SimpleXMLElement;
use Throwable;
use function array_fill;
use function array_key_exists;
use function array_merge;
use function array_pop;
use function array_values;
use function base64_decode;
use function base64_encode;
use function boolval;
use function chr;
use function count;
use function hash_hmac;
use function http_build_query;
use function intval;
use function is_array;
use function is_string;
use function openssl_decrypt;
use function openssl_encrypt;
use function pack;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function property_exists;
use function random_bytes;
use function React\Async\await;
use function simplexml_load_string;
use function sprintf;
use function str_repeat;
use function strlen;
use function strval;
use function substr;
use function unpack;
use const OPENSSL_RAW_DATA;
use const OPENSSL_ZERO_PADDING;

/**
 * Television api interface
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TelevisionApi implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const CONNECTION_TIMEOUT = 10;

	private const SIGNATURE_BYTES_LENGTH = 32;

	private const EVENTS_TIMEOUT_IN_SECONDS = 1;

	private const URN_RENDERING_CONTROL = 'schemas-upnp-org:service:RenderingControl:1';

	private const URN_REMOTE_CONTROL = 'panasonic-com:service:p00NetworkControl:1';

	private const URL_CONTROL_DMR = '/dmr/control_0';

	private const URL_CONTROL_NRC = '/nrc/control_0';

	private const URL_EVENT_NRC = '/nrc/event_0';

	private const URL_CONTROL_NRC_DDD = '/nrc/ddd.xml';

	private const URL_CONTROL_NRC_DEF = '/nrc/sdd_0.xml';

	private bool $isEncrypted;

	private bool $isConnected = false;

	private Entities\API\Session|null $session = null;

	private Log\LoggerInterface $logger;

	private GuzzleHttp\Client|null $client = null;

	private Http\Browser|null $asyncClient = null;

	public function __construct(
		private readonly string $identifier,
		private readonly string $ipAddress,
		private readonly int $port,
		private readonly string|null $appId = null,
		private readonly string|null $encryptionKey = null,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->isEncrypted = $this->appId !== null && $this->encryptionKey !== null;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws Exceptions\Encrypt
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		if ($this->encryptionKey !== null) {
			$this->deriveSessionKeys();
			$this->requestSessionId(false);
		}

		$this->isConnected = true;
	}

	public function disconnect(): void
	{
		$this->session = null;
		$this->isConnected = false;
	}

	public function isConnected(): bool
	{
		return $this->isConnected;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Session)
	 *
	 * @throws Exceptions\Encrypt
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function requestSessionId(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Session
	{
		$deferred = new Promise\Deferred();

		if ($this->session === null) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Session is not created'));
			}

			throw new Exceptions\InvalidState('Session is not created');
		}

		$encInfo = $this->encryptPayload(
			'<X_ApplicationId>' . $this->appId . '</X_ApplicationId>',
			$this->session->getKey(),
			$this->session->getIv(),
			$this->session->getHmacKey(),
		);

		$parameters = '';
		$parameters .= '<X_ApplicationId>' . $this->appId . '</X_ApplicationId>';
		$parameters .= '<X_EncInfo>' . $encInfo . '</X_EncInfo>';

		$result = $this->callXmlRequest(
			self::URL_CONTROL_NRC,
			self::URN_REMOTE_CONTROL,
			'X_GetEncryptSessionId',
			$parameters,
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$body = $this->sanitizeReceivedPayload($response->getBody()->getContents());

						preg_match('/<X_EncResult>(?<encrypted>.*?)<\/X_EncResult>/', $body, $matches);
					} catch (Throwable $ex) {
						$deferred->reject(
							new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex),
						);

						return;
					}

					if (!array_key_exists('encrypted', $matches)) {
						$deferred->reject(new Exceptions\TelevisionApiCall('Could not parse received response'));

						return;
					}

					if ($this->session === null) {
						$deferred->reject(new Exceptions\TelevisionApiCall('Something went wrong. Session was lost'));

						return;
					}

					$payload = $this->decryptPayload(
						$matches['encrypted'],
						$this->session->getKey(),
						$this->session->getIv(),
						$this->session->getHmacKey(),
					);

					preg_match('/<X_SessionId>(?<session_id>.*?)<\/X_SessionId>/', $payload, $matches);

					if (array_key_exists('session_id', $matches)) {
						$this->session->setId($matches['session_id']);
					}

					$this->session->setSeqNum(1);

					$deferred->resolve($this->session);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		try {
			$body = $this->sanitizeReceivedPayload($result->getBody()->getContents());

			preg_match('/<X_EncResult>(?<encrypted>.*?)<\/X_EncResult>/', $body, $matches);
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		if (!array_key_exists('encrypted', $matches)) {
			throw new Exceptions\TelevisionApiCall('Could not parse received response');
		}

		$payload = $this->decryptPayload(
			$matches['encrypted'],
			$this->session->getKey(),
			$this->session->getIv(),
			$this->session->getHmacKey(),
		);

		preg_match('/<X_SessionId>(?<session_id>.*?)<\/X_SessionId>/', $payload, $matches);

		if (array_key_exists('session_id', $matches)) {
			$this->session->setId($matches['session_id']);
		}

		$this->session->setSeqNum(1);

		return $this->session;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\DeviceSpecs)
	 *
	 * @throws Exceptions\TelevisionApiCall
	 */
	public function getSpecs(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\DeviceSpecs
	{
		$deferred = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			$this->ipAddress . ':' . $this->port . self::URL_CONTROL_NRC_DDD,
			[],
			[],
			null,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$specsResponse = simplexml_load_string(
							$this->sanitizeReceivedPayload($response->getBody()->getContents()),
						);

						if (
							!$specsResponse instanceof SimpleXMLElement
							|| !property_exists($specsResponse, 'device')
							|| !$specsResponse->device instanceof SimpleXMLElement
						) {
							$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));
						} else {
							$device = EntityFactory::build(
								Entities\API\DeviceSpecs::class,
								$specsResponse->device,
							);

							$this->needsCrypto()
								->then(static function (bool $needsCrypto) use ($deferred, $device): void {
									$device->setRequiresEncryption($needsCrypto);

									$deferred->resolve($device);
								})
								->otherwise(static function (Throwable $ex) use ($deferred): void {
									$deferred->reject($ex);
								});
						}
					} catch (Throwable $ex) {
						$deferred->reject(
							new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex),
						);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		try {
			$specsResponse = simplexml_load_string($this->sanitizeReceivedPayload($result->getBody()->getContents()));
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		if (
			!$specsResponse instanceof SimpleXMLElement
			|| !property_exists($specsResponse, 'device')
			|| !$specsResponse->device instanceof SimpleXMLElement
		) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid');
		}

		try {
			$device = EntityFactory::build(Entities\API\DeviceSpecs::class, $specsResponse->device);
		} catch (Exceptions\InvalidState $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		$device->setRequiresEncryption($this->needsCrypto(false));

		return $device;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\DeviceApps)
	 *
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function getApps(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\DeviceApps
	{
		$deferred = new Promise\Deferred();

		if ($this->isEncrypted && $this->session === null) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Session is not created'));
			}

			throw new Exceptions\InvalidState('Session is not created');
		}

		$result = $this->callXmlRequest(
			self::URL_CONTROL_NRC,
			self::URN_REMOTE_CONTROL,
			'X_GetAppList',
			'None',
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$body = $this->sanitizeReceivedPayload($response->getBody()->getContents());

						if (
							$this->session !== null
							&& preg_match('/<X_EncResult>(?<encrypted>.*?)<\/X_EncResult>/', $body, $matches) === 1
						) {
							if (!array_key_exists('encrypted', $matches)) {
								$deferred->reject(
									new Exceptions\TelevisionApiCall('Could not parse received response'),
								);

								return;
							}

							$payload = $this->decryptPayload(
								$matches['encrypted'],
								$this->session->getKey(),
								$this->session->getIv(),
								$this->session->getHmacKey(),
							);

							$appsResponse = simplexml_load_string(
								$this->sanitizeReceivedPayload($payload),
							);

							if (
								!$appsResponse instanceof SimpleXMLElement
								|| !property_exists($appsResponse, 'X_GetAppListResponse')
								|| !$appsResponse->X_GetAppListResponse instanceof SimpleXMLElement
								|| !property_exists($appsResponse->X_GetAppListResponse, 'X_AppList')
							) {
								$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));

								return;
							}

							$appsRaw = strval($appsResponse->X_GetAppListResponse->X_AppList);

						} else {
							$appsResponse = simplexml_load_string(
								$this->sanitizeReceivedPayload($body),
							);

							if (
								!$appsResponse instanceof SimpleXMLElement
								|| !property_exists($appsResponse, 'Body')
								|| !$appsResponse->Body instanceof SimpleXMLElement
								|| !property_exists($appsResponse->Body, 'X_GetAppListResponse')
								|| !$appsResponse->Body->X_GetAppListResponse instanceof SimpleXMLElement
								|| !property_exists($appsResponse->Body->X_GetAppListResponse, 'X_AppList')
							) {
								$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));

								return;
							}

							$appsRaw = strval($appsResponse->Body->X_GetAppListResponse->X_AppList);
						}

						if ($appsRaw === '') {
							$deferred->reject(
								new Exceptions\TelevisionApiCall('Television is turned off. Apps could not be loaded'),
							);

							return;
						}

						if (preg_match_all(
							"/'product_id=(?<id>[\dA-Z]+)'(?<name>[^']+)/u",
							$appsRaw,
							$matches,
						) === false) {
							$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));

							return;
						}

						$apps = [];

						foreach ($matches as $appData) {
							if (array_key_exists('id', $appData) && array_key_exists('name', $appData)) {
								$apps[] = new Entities\API\Application($appData['id'], $appData['name']);
							}
						}

						$deferred->resolve(new Entities\API\DeviceApps($apps));
					} catch (Throwable $ex) {
						$deferred->reject(
							new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex),
						);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		try {
			$body = $this->sanitizeReceivedPayload($result->getBody()->getContents());
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		if (
			$this->session !== null
			&& preg_match('/<X_EncResult>(?<encrypted>.*?)<\/X_EncResult>/', $body, $matches) === 1
		) {
			if (!array_key_exists('encrypted', $matches)) {
				throw new Exceptions\TelevisionApiCall('Could not parse received response');
			}

			$payload = $this->decryptPayload(
				$matches['encrypted'],
				$this->session->getKey(),
				$this->session->getIv(),
				$this->session->getHmacKey(),
			);

			$appsResponse = simplexml_load_string(
				$this->sanitizeReceivedPayload($payload),
			);

			if (
				!$appsResponse instanceof SimpleXMLElement
				|| !property_exists($appsResponse, 'X_GetAppListResponse')
				|| !$appsResponse->X_GetAppListResponse instanceof SimpleXMLElement
				|| !property_exists($appsResponse->X_GetAppListResponse, 'X_AppList')
			) {
				throw new Exceptions\TelevisionApiCall('Received response is not valid');
			}

			$appsRaw = strval($appsResponse->X_GetAppListResponse->X_AppList);

		} else {
			$appsResponse = simplexml_load_string(
				$this->sanitizeReceivedPayload($body),
			);

			if (
				!$appsResponse instanceof SimpleXMLElement
				|| !property_exists($appsResponse, 'Body')
				|| !$appsResponse->Body instanceof SimpleXMLElement
				|| !property_exists($appsResponse->Body, 'X_GetAppListResponse')
				|| !$appsResponse->Body->X_GetAppListResponse instanceof SimpleXMLElement
				|| !property_exists($appsResponse->Body->X_GetAppListResponse, 'X_AppList')
			) {
				throw new Exceptions\TelevisionApiCall('Received response is not valid');
			}

			$appsRaw = strval($appsResponse->Body->X_GetAppListResponse->X_AppList);
		}

		if ($appsRaw === '') {
			throw new Exceptions\TelevisionApiCall('Television is turned off. Apps could not be loaded');
		}

		if (preg_match_all("/'product_id=(?<id>[\dA-Z]+)'(?<name>[^']+)/u", $appsRaw, $matches) === false) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid');
		}

		$apps = [];

		foreach ($matches as $appData) {
			if (array_key_exists('id', $appData) && array_key_exists('name', $appData)) {
				$apps[] = new Entities\API\Application($appData['id'], $appData['name']);
			}
		}

		return new Entities\API\DeviceApps($apps);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\DeviceVectorInfo)
	 *
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function getVectorInfo(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\DeviceVectorInfo
	{
		$deferred = new Promise\Deferred();

		if ($this->isEncrypted && $this->session === null) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Session is not created'));
			}

			throw new Exceptions\InvalidState('Session is not created');
		}

		$result = $this->callXmlRequest(
			self::URL_CONTROL_NRC,
			self::URN_REMOTE_CONTROL,
			'X_GetVectorInfo',
			'None',
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$body = $this->sanitizeReceivedPayload($response->getBody()->getContents());

						if (
							$this->session !== null
							&& preg_match('/<X_EncResult>(?<encrypted>.*?)<\/X_EncResult>/', $body, $matches) === 1
						) {
							if (!array_key_exists('encrypted', $matches)) {
								$deferred->reject(
									new Exceptions\TelevisionApiCall('Could not parse received response'),
								);

								return;
							}

							$payload = $this->decryptPayload(
								$matches['encrypted'],
								$this->session->getKey(),
								$this->session->getIv(),
								$this->session->getHmacKey(),
							);

							$vectorInfoResponse = simplexml_load_string(
								$this->sanitizeReceivedPayload($payload),
							);

							if (
								!$vectorInfoResponse instanceof SimpleXMLElement
								|| !property_exists($vectorInfoResponse, 'X_GetVectorInfoResponse')
								|| !$vectorInfoResponse->X_GetVectorInfoResponse instanceof SimpleXMLElement
								|| !property_exists($vectorInfoResponse->X_GetVectorInfoResponse, 'X_PortNumber')
							) {
								$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));

								return;
							}

							$devicePort = intval($vectorInfoResponse->X_GetVectorInfoResponse->X_PortNumber);

						} else {
							$vectorInfoResponse = simplexml_load_string(
								$this->sanitizeReceivedPayload($body),
							);

							if (
								!$vectorInfoResponse instanceof SimpleXMLElement
								|| !property_exists($vectorInfoResponse, 'Body')
								|| !$vectorInfoResponse->Body instanceof SimpleXMLElement
								|| !property_exists($vectorInfoResponse->Body, 'X_GetVectorInfoResponse')
								|| !$vectorInfoResponse->Body->X_GetVectorInfoResponse instanceof SimpleXMLElement
								|| !property_exists($vectorInfoResponse->Body->X_GetVectorInfoResponse, 'X_PortNumber')
							) {
								$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));

								return;
							}

							$devicePort = intval($vectorInfoResponse->Body->X_GetVectorInfoResponse->X_PortNumber);
						}

						$deferred->resolve(new Entities\API\DeviceVectorInfo($devicePort));
					} catch (Throwable $ex) {
						$deferred->reject(
							new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex),
						);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		try {
			$body = $this->sanitizeReceivedPayload($result->getBody()->getContents());
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		if (
			$this->session !== null
			&& preg_match('/<X_EncResult>(?<encrypted>.*?)<\/X_EncResult>/', $body, $matches) === 1
		) {
			if (!array_key_exists('encrypted', $matches)) {
				throw new Exceptions\TelevisionApiCall('Could not parse received response');
			}

			$payload = $this->decryptPayload(
				$matches['encrypted'],
				$this->session->getKey(),
				$this->session->getIv(),
				$this->session->getHmacKey(),
			);

			$vectorInfoResponse = simplexml_load_string(
				$this->sanitizeReceivedPayload($payload),
			);

			if (
				!$vectorInfoResponse instanceof SimpleXMLElement
				|| !property_exists($vectorInfoResponse, 'X_GetVectorInfoResponse')
				|| !$vectorInfoResponse->X_GetVectorInfoResponse instanceof SimpleXMLElement
				|| !property_exists($vectorInfoResponse->X_GetVectorInfoResponse, 'X_PortNumber')
			) {
				throw new Exceptions\TelevisionApiCall('Received response is not valid');
			}

			$devicePort = intval($vectorInfoResponse->X_GetVectorInfoResponse->X_PortNumber);

		} else {
			$vectorInfoResponse = simplexml_load_string(
				$this->sanitizeReceivedPayload($body),
			);

			if (
				!$vectorInfoResponse instanceof SimpleXMLElement
				|| !property_exists($vectorInfoResponse, 'Body')
				|| !$vectorInfoResponse->Body instanceof SimpleXMLElement
				|| !property_exists($vectorInfoResponse->Body, 'X_GetVectorInfoResponse')
				|| !$vectorInfoResponse->Body->X_GetVectorInfoResponse instanceof SimpleXMLElement
				|| !property_exists($vectorInfoResponse->Body->X_GetVectorInfoResponse, 'X_PortNumber')
			) {
				throw new Exceptions\TelevisionApiCall('Received response is not valid');
			}

			$devicePort = intval($vectorInfoResponse->Body->X_GetVectorInfoResponse->X_PortNumber);
		}

		return new Entities\API\DeviceVectorInfo($devicePort);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : int)
	 *
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function getVolume(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|int
	{
		$deferred = new Promise\Deferred();

		if ($this->isEncrypted && $this->session === null) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Session is not created'));
			}

			throw new Exceptions\InvalidState('Session is not created');
		}

		$result = $this->callXmlRequest(
			self::URL_CONTROL_DMR,
			self::URN_RENDERING_CONTROL,
			'GetVolume',
			'<InstanceID>0</InstanceID><Channel>Master</Channel>',
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$body = $this->sanitizeReceivedPayload($response->getBody()->getContents());

						if (
							$this->session !== null
							&& preg_match('/<X_EncResult>(?<encrypted>.*?)<\/X_EncResult>/', $body, $matches) === 1
						) {
							if (!array_key_exists('encrypted', $matches)) {
								$deferred->reject(
									new Exceptions\TelevisionApiCall('Could not parse received response'),
								);

								return;
							}

							$payload = $this->decryptPayload(
								$matches['encrypted'],
								$this->session->getKey(),
								$this->session->getIv(),
								$this->session->getHmacKey(),
							);

							if (
								preg_match('/<CurrentVolume>(?<volume>.*?)<\/CurrentVolume>/', $payload, $matches) !== 1
								|| !array_key_exists('volume', $matches)
							) {
								$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));

								return;
							}

							$deferred->resolve(intval($matches['volume']));
						} else {
							if (
								preg_match('/<CurrentVolume>(?<volume>.*?)<\/CurrentVolume>/', $body, $matches) !== 1
								|| !array_key_exists('volume', $matches)
							) {
								$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));

								return;
							}

							$deferred->resolve(intval($matches['volume']));
						}
					} catch (Throwable $ex) {
						$deferred->reject(
							new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex),
						);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		try {
			$body = $this->sanitizeReceivedPayload($result->getBody()->getContents());
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		if (
			$this->session !== null
			&& preg_match('/<X_EncResult>(?<encrypted>.*?)<\/X_EncResult>/', $body, $matches) === 1
		) {
			if (!array_key_exists('encrypted', $matches)) {
				throw new Exceptions\TelevisionApiCall('Could not parse received response');
			}

			$payload = $this->decryptPayload(
				$matches['encrypted'],
				$this->session->getKey(),
				$this->session->getIv(),
				$this->session->getHmacKey(),
			);

			if (
				preg_match('/<CurrentVolume>(?<volume>.*?)<\/CurrentVolume>/', $payload, $matches) !== 1
				|| !array_key_exists('volume', $matches)
			) {
				throw new Exceptions\TelevisionApiCall('Received response is not valid');
			}

			return intval($matches['volume']);
		}

		if (
			preg_match('/<CurrentVolume>(?<volume>.*?)<\/CurrentVolume>/', $body, $matches) !== 1
			|| !array_key_exists('volume', $matches)
		) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid');
		}

		return intval($matches['volume']);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function setVolume(
		int $volume,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		if ($volume < 0 || $volume > 100) {
			if ($async) {
				return Promise\reject(
					new Exceptions\InvalidState('Bad request to volume control. Volume must be between 0 and 100'),
				);
			}

			throw new Exceptions\InvalidState('Bad request to volume control. Volume must be between 0 and 100');
		}

		if ($this->isEncrypted && $this->session === null) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Session is not created'));
			}

			throw new Exceptions\InvalidState('Session is not created');
		}

		$result = $this->callXmlRequest(
			self::URL_CONTROL_DMR,
			self::URN_RENDERING_CONTROL,
			'SetVolume',
			sprintf('<InstanceID>0</InstanceID><Channel>Master</Channel><DesiredVolume>%d</DesiredVolume>', $volume),
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		return true;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function getMute(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		if ($this->isEncrypted && $this->session === null) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Session is not created'));
			}

			throw new Exceptions\InvalidState('Session is not created');
		}

		$result = $this->callXmlRequest(
			self::URL_CONTROL_DMR,
			self::URN_RENDERING_CONTROL,
			'GetMute',
			'<InstanceID>0</InstanceID><Channel>Master</Channel>',
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$body = $this->sanitizeReceivedPayload($response->getBody()->getContents());

						if (
							$this->session !== null
							&& preg_match('/<X_EncResult>(?<encrypted>.*?)<\/X_EncResult>/', $body, $matches) === 1
						) {
							if (!array_key_exists('encrypted', $matches)) {
								$deferred->reject(
									new Exceptions\TelevisionApiCall('Could not parse received response'),
								);

								return;
							}

							$payload = $this->decryptPayload(
								$matches['encrypted'],
								$this->session->getKey(),
								$this->session->getIv(),
								$this->session->getHmacKey(),
							);

							if (
								preg_match('/<CurrentMute>(?<mute>.*?)<\/CurrentMute>/', $payload, $matches) !== 1
								|| !array_key_exists('mute', $matches)
							) {
								$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));

								return;
							}

							$deferred->resolve(boolval($matches['mute']));
						} else {
							if (
								preg_match('/<CurrentMute>(?<mute>.*?)<\/CurrentMute>/', $body, $matches) !== 1
								|| !array_key_exists('mute', $matches)
							) {
								$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));

								return;
							}

							$deferred->resolve(boolval($matches['mute']));
						}
					} catch (Throwable $ex) {
						$deferred->reject(
							new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex),
						);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		try {
			$body = $this->sanitizeReceivedPayload($result->getBody()->getContents());
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		if (
			$this->session !== null
			&& preg_match('/<X_EncResult>(?<encrypted>.*?)<\/X_EncResult>/', $body, $matches) === 1
		) {
			if (!array_key_exists('encrypted', $matches)) {
				throw new Exceptions\TelevisionApiCall('Could not parse received response');
			}

			$payload = $this->decryptPayload(
				$matches['encrypted'],
				$this->session->getKey(),
				$this->session->getIv(),
				$this->session->getHmacKey(),
			);

			if (
				preg_match('/<CurrentMute>(?<mute>.*?)<\/CurrentMute>/', $payload, $matches) !== 1
				|| !array_key_exists('mute', $matches)
			) {
				throw new Exceptions\TelevisionApiCall('Received response is not valid');
			}

			return boolval($matches['mute']);
		}

		if (
			preg_match('/<CurrentMute>(?<mute>.*?)<\/CurrentMute>/', $body, $matches) !== 1
			|| !array_key_exists('mute', $matches)
		) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid');
		}

		return boolval($matches['mute']);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function setMute(
		bool $status,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		if ($this->isEncrypted && $this->session === null) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Session is not created'));
			}

			throw new Exceptions\InvalidState('Session is not created');
		}

		$result = $this->callXmlRequest(
			self::URL_CONTROL_DMR,
			self::URN_RENDERING_CONTROL,
			'SetMute',
			sprintf(
				'<InstanceID>0</InstanceID><Channel>Master</Channel><DesiredMute>%d</DesiredMute>',
				$status ? 1 : 0,
			),
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		return true;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function sendKey(
		Types\ActionKey|string $key,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		if ($this->isEncrypted && $this->session === null) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Session is not created'));
			}

			throw new Exceptions\InvalidState('Session is not created');
		}

		$result = $this->callXmlRequest(
			self::URL_CONTROL_NRC,
			self::URN_REMOTE_CONTROL,
			'X_SendKey',
			sprintf('<X_KeyEvent>%s</X_KeyEvent>', is_string($key) ? $key : strval($key->getValue())),
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		return true;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function launchApplication(
		string $application,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		if ($this->isEncrypted && $this->session === null) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Session is not created'));
			}

			throw new Exceptions\InvalidState('Session is not created');
		}

		$result = $this->callXmlRequest(
			self::URL_CONTROL_NRC,
			self::URN_REMOTE_CONTROL,
			'X_LaunchApp',
			sprintf(
				'<X_AppType>vc_app</X_AppType><X_LaunchKeyword>%s_id=%s</X_LaunchKeyword>',
				strlen($application) === 16 ? 'product' : 'resource',
				$application,
			),
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		return true;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 */
	public function turnOn(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		try {
			$status = await($this->isTurnedOn());

			if ($status !== false) {
				if ($async) {
					return Promise\resolve(true);
				}

				return true;
			}

			return $this->sendKey(Types\ActionKey::get(Types\ActionKey::POWER), $async);
		} catch (Throwable) {
			if ($async) {
				return Promise\resolve(false);
			}

			return false;
		}
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 */
	public function turnOff(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		try {
			$status = await($this->isTurnedOn());

			if ($status === false) {
				if ($async) {
					return Promise\resolve(true);
				}

				return true;
			}

			return $this->sendKey(Types\ActionKey::get(Types\ActionKey::POWER), $async);
		} catch (Throwable) {
			if ($async) {
				return Promise\resolve(false);
			}

			return false;
		}
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\TelevisionApiCall
	 */
	public function needsCrypto(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			$this->ipAddress . ':' . $this->port . self::URL_CONTROL_NRC_DEF,
			[],
			[],
			null,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function (Message\ResponseInterface $response) use ($deferred): void {
					$deferred->resolve(
						preg_match('/X_GetEncryptSessionId/u', $response->getBody()->getContents()) === 1,
					);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to television');
		}

		try {
			return preg_match('/X_GetEncryptSessionId/u', $result->getBody()->getContents()) === 1;
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function livenessProbe(
		float $timeout = 1.5,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$connector = new Socket\Connector([
			'dns' => '8.8.8.8',
			'timeout' => 10,
			'tls' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
				'check_hostname' => false,
			],
		]);

		$timeoutTimer = $this->eventLoop->addTimer($timeout, static function () use ($deferred): void {
			$deferred->resolve(false);
		});

		$connector->connect($this->ipAddress . ':' . $this->port)
			->then(function () use ($deferred, $timeoutTimer): void {
				$this->eventLoop->cancelTimer($timeoutTimer);

				$deferred->resolve(true);
			})
			->otherwise(static function () use ($deferred): void {
				$deferred->resolve(false);
			});

		return $deferred->promise();
	}

	public function isTurnedOn(): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		try {
			$socket = new Socket\SocketServer('0.0.0.0:0');
		} catch (RuntimeException | InvalidArgumentException) {
			return Promise\resolve(false);
		}

		$socket->on('connection', function (Socket\ConnectionInterface $connection) use ($deferred): void {
			$connection->on('data', static function (string $data) use ($deferred): void {
				$parts = preg_split('/\r?\n\r?\n/', $data);

				if (is_array($parts) && count($parts) === 2) {
					preg_match('/<X_ScreenState>(?<screen_state>\w+)<\/X_ScreenState>/', strval($parts[1]), $matches);

					if (
						array_key_exists('screen_state', $matches)
						&& Utils\Strings::lower($matches['screen_state']) === 'on'
					) {
						$deferred->resolve(true);
					} else {
						$deferred->resolve(false);
					}
				}
			});

			$connection->on('error', function (Throwable $ex) use ($deferred): void {
				$this->logger->error('Something went wrong with subscription socket', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'television-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				$deferred->resolve(false);
			});
		});

		preg_match(
			'/(?<protocol>tcp):\/\/(?<ip_address>[0-9]+.[0-9]+.[0-9]+.[0-9]+)?:(?<port>[0-9]+)?/',
			strval($socket->getAddress()),
			$matches,
		);

		try {
			$client = $this->getClient(false);
		} catch (InvalidArgumentException $ex) {
			$this->logger->error('Could not get http client', [
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
				'type' => 'television-api',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
				'connector' => [
					'identifier' => $this->identifier,
				],
			]);

			return Promise\resolve(false);
		}

		$localIpAddress = Helpers\Network::getLocalAddress();

		if ($localIpAddress === null) {
			$this->logger->error('Could not get connector local address', [
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
				'type' => 'television-api',
				'connector' => [
					'identifier' => $this->identifier,
				],
			]);

			return Promise\resolve(false);
		}

		$localPort = $matches['port'];

		$sid = null;

		try {
			$response = $client->request(
				'SUBSCRIBE',
				'http://' . $this->ipAddress . ':' . $this->port . self::URL_EVENT_NRC,
				[
					GuzzleHttp\RequestOptions::HEADERS => [
						'CALLBACK' => '<http://' . $localIpAddress . ':' . $localPort . '>',
						'NT' => 'upnp:event',
						'TIMEOUT' => 'Second-' . self::EVENTS_TIMEOUT_IN_SECONDS,
					],
				],
			);

			$sidHeader = $response->getHeader('SID');

			if ($sidHeader !== []) {
				$sid = array_pop($sidHeader);
			}
		} catch (GuzzleHttp\Exception\GuzzleException) {
			$socket->close();

			$deferred->resolve(false);
		}

		$this->eventLoop->addTimer(1.5, function () use ($client, $socket, $sid, $deferred): void {
			try {
				$client->request(
					'UNSUBSCRIBE',
					'http://' . $this->ipAddress . ':' . $this->port . self::URL_EVENT_NRC,
					[
						GuzzleHttp\RequestOptions::HEADERS => [
							'SID' => $sid,
						],
						GuzzleHttp\RequestOptions::TIMEOUT => 1,
					],
				);
			} catch (GuzzleHttp\Exception\GuzzleException) {
				// Error could be ignored
			} finally {
				$socket->close();
			}

			$deferred->resolve(false);
		});

		return $deferred->promise();
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : string)
	 *
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function requestPinCode(
		string $name,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|string
	{
		$deferred = new Promise\Deferred();

		// First let's ask for a pin code and get a challenge key back
		$parameters = '<X_DeviceName>' . $name . '</X_DeviceName>';

		$result = $this->callXmlRequest(
			self::URL_CONTROL_NRC,
			self::URN_REMOTE_CONTROL,
			'X_DisplayPinCode',
			$parameters,
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$pinCodeResponse = simplexml_load_string(
							$this->sanitizeReceivedPayload($response->getBody()->getContents()),
						);

						if (
							!$pinCodeResponse instanceof SimpleXMLElement
							|| !property_exists($pinCodeResponse, 'Body')
							|| !$pinCodeResponse->Body instanceof SimpleXMLElement
							|| !property_exists($pinCodeResponse->Body, 'X_DisplayPinCodeResponse')
							|| !$pinCodeResponse->Body->X_DisplayPinCodeResponse instanceof SimpleXMLElement
							|| !property_exists($pinCodeResponse->Body->X_DisplayPinCodeResponse, 'X_ChallengeKey')
						) {
							$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));
						} else {
							$deferred->resolve(
								strval($pinCodeResponse->Body->X_DisplayPinCodeResponse->X_ChallengeKey),
							);
						}
					} catch (Throwable $ex) {
						$deferred->reject(
							new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex),
						);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could not send request to television');
		}

		try {
			$pinCodeResponse = simplexml_load_string($this->sanitizeReceivedPayload($result->getBody()->getContents()));
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		if (
			!$pinCodeResponse instanceof SimpleXMLElement
			|| !property_exists($pinCodeResponse, 'Body')
			|| !$pinCodeResponse->Body instanceof SimpleXMLElement
			|| !property_exists($pinCodeResponse->Body, 'X_DisplayPinCodeResponse')
			|| !$pinCodeResponse->Body->X_DisplayPinCodeResponse instanceof SimpleXMLElement
			|| !property_exists($pinCodeResponse->Body->X_DisplayPinCodeResponse, 'X_ChallengeKey')
		) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid');
		}

		return strval($pinCodeResponse->Body->X_DisplayPinCodeResponse->X_ChallengeKey);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\AuthorizePinCode)
	 *
	 * @throws Exceptions\Encrypt
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\TelevisionApiCall
	 * @throws RuntimeException
	 */
	public function authorizePinCode(
		string $pinCode,
		string $challengeKey,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\AuthorizePinCode
	{
		$deferred = new Promise\Deferred();

		$iv = unpack('C*', strval(base64_decode($challengeKey, true)));

		if ($iv === false) {
			if ($async) {
				return Promise\reject(new Exceptions\TelevisionApiCall('Pairing challenge key could not be parsed'));
			}

			throw new Exceptions\TelevisionApiCall('Pairing challenge key could not be parsed');
		}

		/** @var array<int> $iv */
		$iv = array_values($iv);

		/** @var array<int> $key */
		$key = array_fill(0, 16, 0);

		$i = 0;

		while ($i < 16) {
			$key[$i] = ~$iv[$i + 3] & 0xFF;
			$key[$i + 1] = ~$iv[$i + 2] & 0xFF;
			$key[$i + 2] = ~$iv[$i + 1] & 0xFF;
			$key[$i + 3] = ~$iv[$i] & 0xFF;

			$i += 4;
		}

		// Derive HMAC key from IV & HMAC key mask (taken from libtvconnect.so)
		$hmacKeyMaskValues = [
			0x15,0xC9,0x5A,0xC2,0xB0,0x8A,0xA7,0xEB,0x4E,0x22,0x8F,0x81,0x1E,
			0x34,0xD0,0x4F,0xA5,0x4B,0xA7,0xDC,0xAC,0x98,0x79,0xFA,0x8A,0xCD,
			0xA3,0xFC,0x24,0x4F,0x38,0x54,
		];

		/** @var array<int> $hmacKey */
		$hmacKey = array_fill(0, self::SIGNATURE_BYTES_LENGTH, 0);

		$i = 0;

		while ($i < self::SIGNATURE_BYTES_LENGTH) {
			$hmacKey[$i] = $hmacKeyMaskValues[$i] ^ $iv[$i + 2 & 0xF];
			$hmacKey[$i + 1] = $hmacKeyMaskValues[$i + 1] ^ $iv[$i + 3 & 0xF];
			$hmacKey[$i + 2] = $hmacKeyMaskValues[$i + 2] ^ $iv[$i & 0xF];
			$hmacKey[$i + 3] = $hmacKeyMaskValues[$i + 3] ^ $iv[$i + 1 & 0xF];

			$i += 4;
		}

		// Encrypt X_PinCode argument and send it within an X_AuthInfo tag
		$payload = $this->encryptPayload(
			'<X_PinCode>' . $pinCode . '</X_PinCode>',
			pack('C*', ...$key),
			pack('C*', ...$iv),
			pack('C*', ...$hmacKey),
		);

		// First let's ask for a pin code and get a challenge key back
		$parameters = '<X_AuthInfo>' . $payload . '</X_AuthInfo>';

		$result = $this->callXmlRequest(
			self::URL_CONTROL_NRC,
			self::URN_REMOTE_CONTROL,
			'X_RequestAuth',
			$parameters,
			'u',
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(
					function (Message\ResponseInterface $response) use ($deferred, $key, $iv, $hmacKey): void {
						try {
							$body = $this->sanitizeReceivedPayload($response->getBody()->getContents());

							preg_match('/<X_AuthResult>(?<encrypted>.*?)<\/X_AuthResult>/', $body, $matches);
						} catch (Throwable $ex) {
							$deferred->reject(
								new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex),
							);

							return;
						}

						if (!array_key_exists('encrypted', $matches)) {
							$deferred->reject(new Exceptions\TelevisionApiCall('Could not parse received response'));

							return;
						}

						$payload = $this->decryptPayload(
							$matches['encrypted'],
							pack('C*', ...$key),
							pack('C*', ...$iv),
							pack('C*', ...$hmacKey),
						);

						$appId = $encryptionKey = null;

						preg_match('/<X_ApplicationId>(?<app_id>.*?)<\/X_ApplicationId>/', $payload, $matches);

						if (array_key_exists('app_id', $matches)) {
							$appId = $matches['app_id'];
						}

						preg_match('/<X_Keyword>(?<encryption_key>.*?)<\/X_Keyword>/', $payload, $matches);

						if (array_key_exists('encryption_key', $matches)) {
							$encryptionKey = $matches['encryption_key'];
						}

						$deferred->resolve(new Entities\API\AuthorizePinCode(
							$appId,
							$encryptionKey,
						));
					},
				)
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could not send request to television');
		}

		try {
			$body = $this->sanitizeReceivedPayload($result->getBody()->getContents());

			preg_match('/<X_AuthResult>(?<encrypted>.*?)<\/X_AuthResult>/', $body, $matches);
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		if (!array_key_exists('encrypted', $matches)) {
			throw new Exceptions\TelevisionApiCall('Could not parse received response');
		}

		$payload = $this->decryptPayload(
			$matches['encrypted'],
			pack('C*', ...$key),
			pack('C*', ...$iv),
			pack('C*', ...$hmacKey),
		);

		$appId = $encryptionKey = null;

		preg_match('/<X_ApplicationId>(?<app_id>.*?)<\/X_ApplicationId>/', $payload, $matches);

		if (array_key_exists('app_id', $matches)) {
			$appId = $matches['app_id'];
		}

		preg_match('/<X_Keyword>(?<encryption_key>.*?)<\/X_Keyword>/', $payload, $matches);

		if (array_key_exists('encryption_key', $matches)) {
			$encryptionKey = $matches['encryption_key'];
		}

		return new Entities\API\AuthorizePinCode($appId, $encryptionKey);
	}

	/**
	 * @param array<string, mixed> $headers
	 * @param array<string, mixed> $params
	 *
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Message\ResponseInterface|false)
	 */
	private function callRequest(
		string $method,
		string $requestPath,
		array $headers = [],
		array $params = [],
		string|null $body = null,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface|false
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(sprintf(
			'Request: method = %s url = %s',
			$method,
			$requestPath,
		), [
			'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
			'type' => 'television-api',
			'request' => [
				'method' => $method,
				'url' => $requestPath,
				'headers' => $headers,
				'params' => $params,
				'body' => $body,
			],
			'connector' => [
				'identifier' => $this->identifier,
			],
		]);

		if (count($params) > 0) {
			$requestPath .= '?';
			$requestPath .= http_build_query($params);
		}

		if ($async) {
			try {
				$request = $this->getClient()->request(
					$method,
					$requestPath,
					$headers,
					$body ?? '',
				);

				$request
					->then(
						function (Message\ResponseInterface $response) use ($deferred, $method, $requestPath, $headers, $params, $body): void {
							try {
								$responseBody = $response->getBody()->getContents();

								$response->getBody()->rewind();
							} catch (RuntimeException $ex) {
								throw new Exceptions\TelevisionApiCall(
									'Could not get content from response body',
									$ex->getCode(),
									$ex,
								);
							}

							$this->logger->debug('Received response', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
								'type' => 'television-api',
								'request' => [
									'method' => $method,
									'url' => $requestPath,
									'headers' => $headers,
									'params' => $params,
									'body' => $body,
								],
								'response' => [
									'status_code' => $response->getStatusCode(),
									'body' => $responseBody,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							]);

							$deferred->resolve($response);
						},
						function (Throwable $ex) use ($deferred, $method, $requestPath, $params, $body): void {
							$this->logger->error('Calling api endpoint failed', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
								'type' => 'television-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'request' => [
									'method' => $method,
									'url' => $requestPath,
									'params' => $params,
									'body' => $body,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							]);

							$deferred->reject($ex);
						},
					);
			} catch (Throwable $ex) {
				return Promise\reject($ex);
			}

			return $deferred->promise();
		} else {
			try {
				$response = $this->getClient(false)->request(
					$method,
					$requestPath,
					[
						'headers' => $headers,
						'body' => $body ?? '',
					],
				);

				try {
					$responseBody = $response->getBody()->getContents();

					$response->getBody()->rewind();
				} catch (RuntimeException $ex) {
					throw new Exceptions\TelevisionApiCall(
						'Could not get content from response body',
						$ex->getCode(),
						$ex,
					);
				}

				$this->logger->debug('Received response', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'television-api',
					'request' => [
						'method' => $method,
						'url' => $requestPath,
						'headers' => $headers,
						'params' => $params,
						'body' => $body,
					],
					'response' => [
						'status_code' => $response->getStatusCode(),
						'body' => $responseBody,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return $response;
			} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
				$this->logger->error('Calling api endpoint failed', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'television-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $method,
						'url' => $requestPath,
						'params' => $params,
						'body' => $body,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return false;
			} catch (Exceptions\TelevisionApiCall $ex) {
				$this->logger->error('Received payload is not valid', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'television-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $method,
						'url' => $requestPath,
						'params' => $params,
						'body' => $body,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return false;
			}
		}
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Message\ResponseInterface|false)
	 *
	 * @throws Exceptions\Encrypt
	 * @throws RuntimeException
	 */
	private function callXmlRequest(
		string $url,
		string $urn,
		string $action,
		string $parameters,
		string $bodyElement,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface|false
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(sprintf(
			'Request: url = %s urn = %s',
			$url,
			$urn,
		), [
			'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
			'type' => 'television-api',
			'request' => [
				'url' => $url,
				'urn' => $urn,
				'action' => $action,
				'parameters' => $parameters,
			],
			'connector' => [
				'identifier' => $this->identifier,
			],
		]);

		if (
			$this->isEncrypted
			&& $urn === self::URN_REMOTE_CONTROL
			&& (
				$action !== 'X_GetEncryptSessionId' && $action !== 'X_DisplayPinCode' && $action !== 'X_RequestAuth'
			)
			&& $this->session !== null
		) {
			$this->session->incrementSeqNum();

			$command = '';
			$command .= '<X_SessionId>' . $this->session->getId() . '</X_SessionId>';
			$command .= '<X_SequenceNumber>';
			$command .= substr('00000000' . $this->session->getSeqNum(), -8);
			$command .= '</X_SequenceNumber>';
			$command .= '<X_OriginalCommand>';
			$command .= '<' . $bodyElement . ':' . $action . ' xmlns:' . $bodyElement . '="urn:' . $urn . '">';
			$command .= $parameters;
			$command .= '</' . $bodyElement . ':' . $action . '>';
			$command .= '</X_OriginalCommand>';

			$encryptedCommand = $this->encryptPayload(
				$command,
				$this->session->getKey(),
				$this->session->getIv(),
				$this->session->getHmacKey(),
			);

			$action = 'X_EncryptedCommand';

			$parameters = '';
			$parameters .= '<X_ApplicationId>' . $this->appId . '</X_ApplicationId>';
			$parameters .= '<X_EncInfo>' . $encryptedCommand . '</X_EncInfo>';
		}

		$body = '';
		$body .= '<?xml version="1.0" encoding="utf-8"?>';
		$body .= '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
		$body .= '<s:Body>';
		$body .= '<' . $bodyElement . ':' . $action . ' xmlns:' . $bodyElement . '="urn:' . $urn . '">';
		$body .= $parameters;
		$body .= '</' . $bodyElement . ':' . $action . '>';
		$body .= '</s:Body>';
		$body .= '</s:Envelope>';

		$headers = [
			'Content-Length' => strlen($body),
			'Content-Type' => 'text/xml; charset="utf-8"',
			'SOAPAction' => '"urn:' . $urn . '#' . $action . '"',
			'Cache-Control' => 'no-cache',
			'Pragma' => 'no-cache',
			'Accept' => 'text/xml',
		];

		if ($async) {
			try {
				$request = $this->getClient()->post(
					'http://' . $this->ipAddress . ':' . $this->port . $url,
					$headers,
					$body,
				);

				$request
					->then(
						function (Message\ResponseInterface $response) use ($deferred, $url, $urn, $action, $parameters): void {
							try {
								$responseBody = $response->getBody()->getContents();

								$response->getBody()->rewind();
							} catch (RuntimeException $ex) {
								throw new Exceptions\TelevisionApiCall(
									'Could not get content from response body',
									$ex->getCode(),
									$ex,
								);
							}

							$this->logger->debug('Received response', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
								'type' => 'television-api',
								'request' => [
									'url' => $url,
									'urn' => $urn,
									'action' => $action,
									'parameters' => $parameters,
								],
								'response' => [
									'status_code' => $response->getStatusCode(),
									'body' => $responseBody,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							]);

							$deferred->resolve($response);
						},
						function (Throwable $ex) use ($deferred, $url, $urn, $action, $parameters): void {
							$this->logger->error('Calling api endpoint failed', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
								'type' => 'television-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'request' => [
									'url' => $url,
									'urn' => $urn,
									'action' => $action,
									'parameters' => $parameters,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							]);

							$deferred->reject($ex);
						},
					);
			} catch (Throwable $ex) {
				return Promise\reject($ex);
			}

			return $deferred->promise();
		} else {
			try {
				$response = $this->getClient(false)->post(
					$this->ipAddress . ':' . $this->port . $url,
					[
						'headers' => $headers,
						'body' => $body,
					],
				);

				try {
					$responseBody = $response->getBody()->getContents();

					$response->getBody()->rewind();
				} catch (RuntimeException $ex) {
					throw new Exceptions\TelevisionApiCall(
						'Could not get content from response body',
						$ex->getCode(),
						$ex,
					);
				}

				$this->logger->debug('Received response', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'television-api',
					'request' => [
						'url' => $url,
						'urn' => $urn,
						'action' => $action,
						'parameters' => $parameters,
					],
					'response' => [
						'status_code' => $response->getStatusCode(),
						'body' => $responseBody,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return $response;
			} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
				if ($ex instanceof GuzzleHttp\Exception\BadResponseException) {
					echo $ex->getResponse()->getBody()->getContents();
				}

				$this->logger->error('Calling api endpoint failed', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'television-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'url' => $url,
						'urn' => $urn,
						'action' => $action,
						'parameters' => $parameters,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return false;
			} catch (Exceptions\TelevisionApiCall $ex) {
				$this->logger->error('Received payload is not valid', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'television-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'url' => $url,
						'urn' => $urn,
						'action' => $action,
						'parameters' => $parameters,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return false;
			}
		}
	}

	private function deriveSessionKeys(): void
	{
		if ($this->encryptionKey === null) {
			return;
		}

		$iv = unpack('C*', strval(base64_decode($this->encryptionKey, true)));

		if ($iv === false) {
			return;
		}

		/** @var array<int> $iv */
		$iv = array_values($iv);

		/** @var array<int> $sessionKey */
		$sessionKey = array_fill(0, 16, 0);

		$i = 0;

		while ($i < 16) {
			$sessionKey[$i] = $iv[$i + 2];
			$sessionKey[$i + 1] = $iv[$i + 3];
			$sessionKey[$i + 2] = $iv[$i];
			$sessionKey[$i + 3] = $iv[$i + 1];

			$i += 4;
		}

		$this->session = new Entities\API\Session(
			pack('C*', ...$sessionKey),
			// Derive key from IV
			pack('C*', ...$iv),
			// HMAC key for comms is just the IV repeated twice
			pack('C*', ...array_merge($iv, $iv)),
		);
	}

	/**
	 * @throws Exceptions\Encrypt
	 */
	private function encryptPayload(string $data, string $key, string $iv, string $hmacKey): string
	{
		try {
			// Start with 12 random bytes
			$message = pack('C*', ...((array) unpack('C*', random_bytes(12))));
		} catch (Throwable $ex) {
			throw new Exceptions\Encrypt('Preparing payload header failed', $ex->getCode(), $ex);
		}

		// Add 4 bytes (big endian) of the length of data
		$message .= pack('N', strlen($data));

		$message .= $data;

		$message = $message . str_repeat(chr(0), 16 - (strlen($message) % 16));

		// Encrypt the payload
		$cipherText = openssl_encrypt(
			$message,
			'AES-128-CBC',
			$key,
			OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
			$iv,
		);

		if ($cipherText === false) {
			throw new Exceptions\Encrypt('Payload could not be encrypted');
		}

		// Compute HMAC-SHA-256
		$sig = hash_hmac('sha256', $cipherText, $hmacKey, true);

		// Concat HMAC with AES encrypted payload
		return base64_encode($cipherText . $sig);
	}

	/**
	 * @throws Exceptions\Decrypt
	 */
	private function decryptPayload(string $data, string $key, string $iv, string $hmacKey): string
	{
		$decodedWithSignature = base64_decode($data, true);

		if ($decodedWithSignature === false) {
			throw new Exceptions\Decrypt('Payload could not be decoded');
		}

		$decoded = substr($decodedWithSignature, 0, -self::SIGNATURE_BYTES_LENGTH);
		$signature = substr($decodedWithSignature, -self::SIGNATURE_BYTES_LENGTH);

		$calculatedSignature = hash_hmac('sha256', $decoded, $hmacKey, true);

		if ($signature !== $calculatedSignature) {
			throw new Exceptions\Decrypt('Payload could not be decrypted. Signatures are different');
		}

		$result = openssl_decrypt(
			$decoded,
			'AES-128-CBC',
			$key,
			OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
			$iv,
		);

		if ($result === false) {
			throw new Exceptions\Decrypt('Payload could not be decrypted');
		}

		$decrypted = unpack('C*', $result);

		if ($decrypted === false) {
			throw new Exceptions\Decrypt('Payload could not be decrypted');
		}

		$decrypted = array_values($decrypted);

		$message = [];

		// The valid decrypted data starts at byte offset 16
		for ($i = 16; $i < count($decrypted); $i++) {
			// Strip ending
			if ($decrypted[$i] === 0) {
				break;
			}

			$message[] = $decrypted[$i];
		}

		return pack('C*', ...$message);
	}

	private function sanitizeReceivedPayload(string $payload): string
	{
		$sanitized = preg_replace('/<(\/?)\w+:(\w+\/?) ?(\w+:\w+.*)?>/', '<$1$2>', $payload);

		if (!is_string($sanitized)) {
			return $payload;
		}

		return $sanitized;
	}

	/**
	 * @return ($async is true ? Http\Browser : GuzzleHttp\Client)
	 *
	 * @throws InvalidArgumentException
	 */
	private function getClient(bool $async = true): GuzzleHttp\Client|Http\Browser
	{
		if ($async) {
			if ($this->asyncClient === null) {
				$this->asyncClient = new Http\Browser(
					new Connector(
						[
							'timeout' => self::CONNECTION_TIMEOUT,
						],
						$this->eventLoop,
					),
					$this->eventLoop,
				);
			}

			return $this->asyncClient;
		} else {
			if ($this->client === null) {
				$this->client = new GuzzleHttp\Client();
			}

			return $this->client;
		}
	}

}
