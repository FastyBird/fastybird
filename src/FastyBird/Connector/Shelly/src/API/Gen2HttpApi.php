<?php declare(strict_types = 1);

/**
 * Gen2HttpApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           24.12.22
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function intval;
use function preg_match;
use function sprintf;
use function uniqid;

/**
 * Generation 2 device http API interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen2HttpApi extends HttpApi
{

	use Gen2;
	use Nette\SmartObject;

	private const DEVICE_INFORMATION_ENDPOINT = 'http://%s/rpc/Shelly.GetDeviceInfo';

	private const DEVICE_CONFIGURATION_ENDPOINT = 'http://%s/rpc/Shelly.GetConfig';

	private const DEVICE_STATUS_ENDPOINT = 'http://%s/rpc/Shelly.GetStatus';

	private const DEVICE_ACTION_ENDPOINT = 'http://%s/rpc';

	private const DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME = 'gen2_http_shelly.json';

	private const DEVICE_CONFIG_MESSAGE_SCHEMA_FILENAME = 'gen2_http_config.json';

	private const DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'gen2_http_status.json';

	public function __construct(
		protected readonly MetadataSchemas\Validator $schemaValidator,
		EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		parent::__construct($eventLoop, $logger);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	public function getDeviceInformation(
		string $address,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen2\DeviceInformation
	{
		if ($async) {
			$deferred = new Promise\Deferred();

			$this->callAsyncRequest(
				'GET',
				sprintf(self::DEVICE_INFORMATION_ENDPOINT, $address),
			)
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve(
							$this->parseDeviceInformationResponse(
								$response->getBody()->getContents(),
								self::DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME,
							),
						);
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseDeviceInformationResponse(
			$this->callRequest(
				'GET',
				sprintf(self::DEVICE_INFORMATION_ENDPOINT, $address),
			)->getBody()->getContents(),
			self::DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME,
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	public function getDeviceConfiguration(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen2\DeviceConfiguration
	{
		if ($async) {
			$deferred = new Promise\Deferred();

			$this->callAsyncRequest(
				'GET',
				sprintf(self::DEVICE_CONFIGURATION_ENDPOINT, $address),
				[],
				[],
				null,
				self::AUTHORIZATION_DIGEST,
				$username,
				$password,
			)
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve(
							$this->parseDeviceConfigurationResponse(
								$response->getBody()->getContents(),
								self::DEVICE_CONFIG_MESSAGE_SCHEMA_FILENAME,
							),
						);
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseDeviceConfigurationResponse(
			$this->callRequest(
				'GET',
				sprintf(self::DEVICE_CONFIGURATION_ENDPOINT, $address),
				[],
				[],
				null,
				self::AUTHORIZATION_BASIC,
				$username,
				$password,
			)->getBody()->getContents(),
			self::DEVICE_CONFIG_MESSAGE_SCHEMA_FILENAME,
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	public function getDeviceStatus(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen2\DeviceStatus
	{
		if ($async) {
			$deferred = new Promise\Deferred();

			$url = sprintf(self::DEVICE_STATUS_ENDPOINT, $address);

			$this->callAsyncRequest(
				'GET',
				$url,
				[],
				[],
				null,
				self::AUTHORIZATION_DIGEST,
				$username,
				$password,
			)
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve(
							$this->parseDeviceStatusResponse(
								$response->getBody()->getContents(),
								self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
							),
						);
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseDeviceStatusResponse(
			$this->callRequest(
				'GET',
				sprintf(self::DEVICE_STATUS_ENDPOINT, $address),
				[],
				[],
				null,
				self::AUTHORIZATION_BASIC,
				$username,
				$password,
			)->getBody()->getContents(),
			self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
		);
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 */
	public function setDeviceStatus(
		string $address,
		string|null $username,
		string|null $password,
		string $component,
		int|float|string|bool $value,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		if (
			preg_match(self::$PROPERTY_COMPONENT, $component, $propertyMatches) !== 1
			|| !array_key_exists('component', $propertyMatches)
			|| !array_key_exists('identifier', $propertyMatches)
			|| !array_key_exists('attribute', $propertyMatches)
		) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Property identifier is not in expected format'));
			}

			throw new Exceptions\HttpApiCall('Property identifier is not in expected format');
		}

		try {
			$componentMethod = $this->buildComponentMethod($component);

		} catch (Exceptions\InvalidState) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Component action could not be created'));
			}

			throw new Exceptions\HttpApiCall('Component action could not be created');
		}

		try {
			$body = Utils\Json::encode([
				'id' => uniqid(),
				'method' => $componentMethod,
				'params' => [
					'id' => intval($propertyMatches['identifier']),
					$propertyMatches['attribute'] => $value,
				],
			]);
		} catch (Utils\JsonException $ex) {
			return Promise\reject(new Exceptions\InvalidState(
				'Message body could not be encoded',
				$ex->getCode(),
				$ex,
			));
		}

		if ($async) {
			$deferred = new Promise\Deferred();

			$this->callAsyncRequest(
				'POST',
				sprintf(
					self::DEVICE_ACTION_ENDPOINT,
					$address,
				),
				[],
				[],
				$body,
				self::AUTHORIZATION_DIGEST,
				$username,
				$password,
			)
				->then(static function () use ($deferred): void {
					$deferred->resolve();
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		$response = $this->callRequest(
			'POST',
			sprintf(
				self::DEVICE_ACTION_ENDPOINT,
				$address,
			),
			[],
			[],
			$body,
			self::AUTHORIZATION_BASIC,
			$username,
			$password,
		);

		return $response->getStatusCode() === StatusCodeInterface::STATUS_OK;
	}

}
