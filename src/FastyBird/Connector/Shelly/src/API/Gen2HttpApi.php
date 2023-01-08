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
use FastyBird\Connector\Shelly\Types;
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
use function preg_match;
use function sprintf;
use function uniqid;

/**
 * Generation 2 device http api interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen2HttpApi extends HttpApi
{

	use Nette\SmartObject;

	private const DEVICE_INFORMATION_ENDPOINT = 'http://%s/rpc/Shelly.GetDeviceInfo';

	private const DEVICE_CONFIGURATION_ENDPOINT = 'http://%s/rpc/Shelly.GetConfig';

	private const DEVICE_STATUS_ENDPOINT = 'http://%s/rpc/Shelly.GetStatus';

	private const DEVICE_ACTION_ENDPOINT = 'http://%s/rpc';

	public const DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME = 'gen2_http_shelly.json';

	public const DEVICE_CONFIG_MESSAGE_SCHEMA_FILENAME = 'gen2_http_config.json';

	public const DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'gen2_http_status.json';

	private const COMPONENT_KEY = '/^(?P<component>[a-zA-Z]+)(:(?P<channel>[0-9_]+))?$/';

	public function __construct(
		private readonly EntityFactory $entityFactory,
		private readonly MetadataSchemas\Validator $schemaValidator,
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
						$deferred->resolve($this->parseDeviceInformationResponse($response));
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
			),
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
						$deferred->resolve($this->parseDeviceConfigurationResponse($response));
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
			),
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
						$deferred->resolve($this->parseDeviceStatusResponse($response));
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
			),
		);
	}

	/**
	 * @param array<string, string|int|float|bool> $params
	 *
	 * @throws Exceptions\HttpApiCall
	 */
	public function setDeviceStatus(
		string $address,
		string|null $username,
		string|null $password,
		string $method,
		array $params,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		try {
			$body = Utils\Json::encode([
				'id' => uniqid(),
				'method' => $method,
				'params' => $params,
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

	/**
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function parseDeviceInformationResponse(
		Message\ResponseInterface $response,
	): Entities\API\Gen2\DeviceInformation
	{
		$parsedMessage = $this->schemaValidator->validate(
			$response->getBody()->getContents(),
			$this->getSchemaFilePath(self::DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME),
		);

		return $this->entityFactory->build(
			Entities\API\Gen2\DeviceInformation::class,
			$parsedMessage,
		);
	}

	/**
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function parseDeviceConfigurationResponse(
		Message\ResponseInterface $response,
	): Entities\API\Gen2\DeviceConfiguration
	{
		$parsedMessage = $this->schemaValidator->validate(
			$response->getBody()->getContents(),
			$this->getSchemaFilePath(self::DEVICE_CONFIG_MESSAGE_SCHEMA_FILENAME),
		);

		$switches = $covers = $lights = $inputs = [];
		$temperature = $humidity = null;

		foreach ($parsedMessage as $key => $configuration) {
			if (
				$configuration instanceof Utils\ArrayHash
				&& preg_match(self::COMPONENT_KEY, $key, $componentMatches) === 1
				&& array_key_exists('component', $componentMatches)
				&& Types\ComponentType::isValidValue($componentMatches['component'])
			) {
				if ($componentMatches['component'] === Types\ComponentType::TYPE_SWITCH) {
					$switches[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceSwitchConfiguration::class,
						$configuration,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_COVER) {
					$covers[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceCoverConfiguration::class,
						$configuration,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_LIGHT) {
					$lights[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceLightConfiguration::class,
						$configuration,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_INPUT) {
					$inputs[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceInputConfiguration::class,
						$configuration,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_TEMPERATURE) {
					$temperature = $this->entityFactory->build(
						Entities\API\Gen2\DeviceTemperatureConfiguration::class,
						$configuration,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_HUMIDITY) {
					$humidity = $this->entityFactory->build(
						Entities\API\Gen2\DeviceHumidityConfiguration::class,
						$configuration,
					);
				}
			}
		}

		return new Entities\API\Gen2\DeviceConfiguration(
			$switches,
			$covers,
			$inputs,
			$lights,
			$temperature,
			$humidity,
		);
	}

	/**
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function parseDeviceStatusResponse(
		Message\ResponseInterface $response,
	): Entities\API\Gen2\DeviceStatus
	{
		$parsedMessage = $this->schemaValidator->validate(
			$response->getBody()->getContents(),
			$this->getSchemaFilePath(self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME),
		);

		$switches = $covers = $lights = $inputs = [];
		$ethernet = $wifi = $temperature = $humidity = null;

		foreach ($parsedMessage as $key => $status) {
			if (
				$status instanceof Utils\ArrayHash
				&& preg_match(self::COMPONENT_KEY, $key, $componentMatches) === 1
				&& array_key_exists('component', $componentMatches)
				&& Types\ComponentType::isValidValue($componentMatches['component'])
			) {
				if ($componentMatches['component'] === Types\ComponentType::TYPE_SWITCH) {
					$switches[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceSwitchStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_COVER) {
					$covers[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceCoverStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_LIGHT) {
					$lights[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceLightStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_INPUT) {
					$inputs[] = $this->entityFactory->build(
						Entities\API\Gen2\DeviceInputStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_TEMPERATURE) {
					$temperature = $this->entityFactory->build(
						Entities\API\Gen2\DeviceTemperatureStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_HUMIDITY) {
					$humidity = $this->entityFactory->build(
						Entities\API\Gen2\DeviceHumidityStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_ETHERNET) {
					$ethernet = $this->entityFactory->build(
						Entities\API\Gen2\EthernetStatus::class,
						$status,
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TYPE_WIFI) {
					$wifi = $this->entityFactory->build(
						Entities\API\Gen2\WifiStatus::class,
						$status,
					);
				}
			}
		}

		return new Entities\API\Gen2\DeviceStatus(
			$switches,
			$covers,
			$inputs,
			$lights,
			$temperature,
			$humidity,
			$ethernet,
			$wifi,
		);
	}

}
