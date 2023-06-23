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

use Clue\React\Multicast;
use Evenement;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Exceptions;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use Ratchet;
use React\EventLoop;
use React\Http;
use React\Promise;
use React\Socket;
use React\Socket\Connector;
use RuntimeException;
use Sabre;
use Throwable;
use function array_fill;
use function array_key_exists;
use function array_merge;
use function base64_decode;
use function count;
use function hash_hmac;
use function http_build_query;
use function is_array;
use function openssl_decrypt;
use function openssl_encrypt;
use function pack;
use function preg_match;
use function random_bytes;
use function React\Async\await;
use function sprintf;
use function strlen;
use function strval;
use function substr;
use function unpack;
use const OPENSSL_RAW_DATA;

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

	private bool $isEncrypted = false;

	private string|null $challengeKey = null;

	private string|null $sessionKey = null;

	private string|null $sessionIv = null;

	private string|null $sessionHmacKey = null;

	private string|null $sessionId = null;

	private int|null $sessionSeqNum = null;

	private Log\LoggerInterface $logger;

	private GuzzleHttp\Client|null $client = null;

	private Http\Browser|null $asyncClient = null;

	public function __construct(
		private readonly string $identifier,
		private readonly string $ipAddress,
		private readonly int $port,
		private readonly string|null $appId = null,
		private readonly string|null $encryptionKey = null,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->isEncrypted = $this->appId !== null && $this->encryptionKey !== null;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function connect(): void
	{
	}

	public function disconnect(): void
	{
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Specs\Device)
	 *
	 * @throws Exceptions\TelevisionApiCall
	 */
	public function getSpecs(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Specs\Device
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
						$service = new Sabre\Xml\Service();

						$service->mapValueObject(
							'{urn:schemas-upnp-org:device-1-0}root',
							Entities\API\Specs\Root::class,
						);
						$service->mapValueObject(
							'{urn:schemas-upnp-org:device-1-0}device',
							Entities\API\Specs\Device::class,
						);

						$specs = $service->parse($this->sanitizeReceivedPayload($response->getBody()->getContents()));

						if (!$specs instanceof Entities\API\Specs\Root) {
							$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));
						} else {
							$device = $specs->getDevice();

							if ($device === null) {
								$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));

								return;
							}

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
			throw new Exceptions\TelevisionApiCall('Could send data to cloud server');
		}

		$service = new Sabre\Xml\Service();

		$service->mapValueObject('{urn:schemas-upnp-org:device-1-0}root', Entities\API\Specs\Root::class);
		$service->mapValueObject('{urn:schemas-upnp-org:device-1-0}device', Entities\API\Specs\Device::class);

		try {
			$specs = $service->parse($this->sanitizeReceivedPayload($result->getBody()->getContents()));
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		if (!$specs instanceof Entities\API\Specs\Root) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid');
		}

		$device = $specs->getDevice();

		if ($device === null) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid');
		}

		$device->setRequiresEncryption($this->needsCrypto(false));

		return $device;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Specs\Device)
	 *
	 * @throws Exceptions\TelevisionApiCall
	 */
	public function getApps(
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Specs\Device
	{
		$deferred = new Promise\Deferred();

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
				->then(static function (Message\ResponseInterface $response): void {
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could send data to cloud server');
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
			throw new Exceptions\TelevisionApiCall('Could send data to cloud server');
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
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface
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

		$httpServer = new Http\HttpServer(
			new Http\Middleware\StreamingRequestMiddleware(),
			function (Message\ServerRequestInterface $request): Message\ResponseInterface {
				var_dump('EVENT');
				var_dump($request->getBody()->getContents());

				return Http\Message\Response::plaintext('OK');
			},
		);

		try {
			$socket = new Socket\SocketServer('0.0.0.0:43323');
		} catch (RuntimeException | InvalidArgumentException) {
			return Promise\resolve(false);
		}

		$httpServer->listen($socket);

		var_dump($socket->getAddress());
		preg_match('/(?<protocol>tcp):\/\/(?<ip_address>[0-9]+.[0-9]+.[0-9]+.[0-9]+)?:(?<port>[0-9]+)?/', $socket->getAddress(), $matches);

		try {
			$client = $this->getClient(false);
		} catch (InvalidArgumentException) {
			return Promise\resolve(false);
		}

		$localIpAddress = '10.10.0.222';
		$localPort = $matches['port'];

		$this->eventLoop->addTimer(5, function () use ($localIpAddress, $localPort, $client, $httpServer, $socket, $deferred): void {
			var_dump([
				'headers' => [
					'CALLBACK' => '<http://' . $localIpAddress . ':' . $localPort . '>',
					'NT' => 'upnp:event',
					'TIMEOUT' => 'Second-' . self::EVENTS_TIMEOUT_IN_SECONDS
				],
			]);
			try {
				$response = $client->request(
					'SUBSCRIBE',
					'http://' . $this->ipAddress . ':' . $this->port . self::URL_EVENT_NRC,
					[
						'headers' => [
							'CALLBACK' => '<http://' . $localIpAddress . ':' . $localPort . '>',
							'NT' => 'upnp:event',
							'TIMEOUT' => 'Second-' . self::EVENTS_TIMEOUT_IN_SECONDS
						],
					]
				);
			} catch (GuzzleHttp\Exception\GuzzleException) {
				$socket->close();

				$deferred->resolve(false);
			}
			var_dump($response->getStatusCode());
			var_dump($response->getHeaders());
			var_dump($response->getBody()->getContents());
		});

		return $deferred->promise();
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\TelevisionApiCall
	 */
	public function requestPinCode(
		string $name,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface|bool
	{
		$deferred = new Promise\Deferred();

		$isTurnedOn = await($this->isTurnedOn());

		if ($isTurnedOn === false) {
			if ($async) {
				return Promise\reject(new Exceptions\TelevisionApiCall('Television is turned off'));
			}

			throw new Exceptions\TelevisionApiCall('Television is turned off');
		}

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
						$service = new Sabre\Xml\Service();

						$service->mapValueObject('{}Envelope', Entities\API\RequestPinCode\Envelope::class);
						$service->mapValueObject('{}Body', Entities\API\RequestPinCode\Body::class);
						$service->mapValueObject('{}X_DisplayPinCodeResponse', Entities\API\RequestPinCode\DisplayPinCodeResponse::class);

						$pinCodeResponse = $service->parse($this->sanitizeReceivedPayload($response->getBody()->getContents()));

						if (!$pinCodeResponse instanceof Entities\API\RequestPinCode\Envelope) {
							$deferred->reject(new Exceptions\TelevisionApiCall('Received response is not valid'));
						} else {
							$this->challengeKey = $pinCodeResponse->getBody()?->getXDisplayPinCodeResponse()?->getXChallengeKey();

							$deferred->resolve(true);
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

		$service = new Sabre\Xml\Service();

		$service->mapValueObject('{}Envelope', Entities\API\RequestPinCode\Envelope::class);
		$service->mapValueObject('{}Body', Entities\API\RequestPinCode\Body::class);
		$service->mapValueObject('{}X_DisplayPinCodeResponse', Entities\API\RequestPinCode\DisplayPinCodeResponse::class);

		try {
			$pinCodeResponse = $service->parse($this->sanitizeReceivedPayload($result->getBody()->getContents()));
		} catch (Throwable $ex) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid', $ex->getCode(), $ex);
		}

		if (!$pinCodeResponse instanceof Entities\API\RequestPinCode\Envelope) {
			throw new Exceptions\TelevisionApiCall('Received response is not valid');
		}

		$this->challengeKey = $pinCodeResponse->getBody()?->getXDisplayPinCodeResponse()?->getXChallengeKey();

		return true;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\Encrypt
	 * @throws Exceptions\Decrypt
	 * @throws Exceptions\TelevisionApiCall
	 */
	public function authorizePinCode(
		string $pinCode,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface|bool
	{
		$deferred = new Promise\Deferred();

		if ($this->challengeKey === null) {
			if ($async) {
				return Promise\reject(new Exceptions\TelevisionApiCall('Pairing challenge key is missing'));
			}

			throw new Exceptions\TelevisionApiCall('Pairing challenge key is missing');
		}

		$iv = unpack('C*', base64_decode($this->challengeKey, true));

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
		$hmacKey = array_fill(0, self:: SIGNATURE_BYTES_LENGTH, 0);

		$i = 0;

		while ($i < self:: SIGNATURE_BYTES_LENGTH) {
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
				->then(static function (Message\ResponseInterface $response) use ($deferred): void {
					var_dump($response->getBody()->getContents());

					$deferred->resolve(true);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\TelevisionApiCall('Could not send request to television');
		}

		$body = $this->sanitizeReceivedPayload($result->getBody()->getContents());

		try {
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

		var_dump($body);
		var_dump($payload);

		// Set session application ID and encryption key
		//$this->appId = $payload.find(".//X_ApplicationId").text
		//$this->encryptionKey = $payload.find(".//X_Keyword").text

		return true;
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
			&& $this->sessionKey !== null
			&& $this->sessionIv !== null
			&& $this->sessionHmacKey !== null
			&& $this->sessionId !== null
			&& $this->sessionSeqNum !== null
		) {
			$this->sessionSeqNum += 1;

			$command = '';
			$command .= '<X_SessionId>' . $this->sessionId . '</X_SessionId>';
			$command .= '<X_SequenceNumber>';
			$command .= substr('00000000' . $this->sessionSeqNum, -8);
			$command .= '</X_SequenceNumber>';
			$command .= '<X_OriginalCommand>';
			$command .= '<' . $bodyElement . ':' . $action . ' xmlns:' . $bodyElement . '="urn:' . $urn . '">';
			$command .= $parameters;
			$command .= '</' . $bodyElement . ':' . $action . '>';
			$command .= '</X_OriginalCommand>';

			$encryptedCommand = $this->encryptPayload(
				$command,
				$this->sessionKey,
				$this->sessionIv,
				$this->sessionHmacKey,
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
					$this->ipAddress . ':' . $this->port . $url,
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

		// Derive key from IV
		$this->sessionIv = pack('C*', ...$iv);

		$sessionKey = [];

		$i = 1;

		while ($i < 17) {
			$sessionKey[$i] = $iv[$i + 2];
			$sessionKey[$i + 1] = $iv[$i + 3];
			$sessionKey[$i + 2] = $iv[$i];
			$sessionKey[$i + 3] = $iv[$i + 1];

			$i += 4;
		}

		$this->sessionKey = pack('C*', $sessionKey);

		// HMAC key for comms is just the IV repeated twice
		$this->sessionHmacKey = pack('C*', array_merge($iv, $iv));
	}

	/**
	 * @throws Exceptions\Encrypt
	 */
	private function encryptPayload(string $data, string $key, string $iv, string $hmacKey): string
	{
		try {
			// Start with 12 random bytes
			$message = pack('C*', ...((array)unpack('C*', random_bytes(12))));
		} catch (Throwable $ex) {
			throw new Exceptions\Encrypt('Preparing payload header failed', $ex->getCode(), $ex);
		}

		// Add 4 bytes (big endian) of the length of data
		$message .= pack('N', strlen($data));

		$message .= $data;

		// Encrypt the payload
		$cipherText = openssl_encrypt(
			$message,
			'AES-128-CBC',
			$key,
			OPENSSL_RAW_DATA,
			$iv,
		);

		if ($cipherText === false) {
			throw new Exceptions\Encrypt('Payload could not be encrypted');
		}

		// Compute HMAC-SHA-256
		$sig = hash_hmac('sha256', $cipherText, $hmacKey, true);

		// Concat HMAC with AES encrypted payload
		return base64_encode($cipherText) . base64_encode($sig);
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

		$decoded = substr($decodedWithSignature, 0, -self:: SIGNATURE_BYTES_LENGTH);
		$signature = substr($decodedWithSignature, -self:: SIGNATURE_BYTES_LENGTH);

		$calculatedSignature = hash_hmac('sha256', $decodedWithSignature, $hmacKey, true);

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

		// The valid decrypted data starts at byte offset 16
		return Utils\Strings::substring($result, 16, intval(strpos($result, pack('C*', '0'), 16)));
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
