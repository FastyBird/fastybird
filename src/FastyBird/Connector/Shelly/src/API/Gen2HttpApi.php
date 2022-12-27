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
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use Throwable;
use function array_key_exists;
use function preg_match;
use function sprintf;

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
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		parent::__construct($this->eventLoop, $logger);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getDeviceInformation(
		string $address,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::DEVICE_INFORMATION_ENDPOINT, $address),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					$parsedMessage = $this->schemaValidator->validate(
						$response->getBody()->getContents(),
						$this->getSchemaFilePath(self::DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME),
					);

					$information = $this->entityFactory->build(
						Entities\API\Gen2\DeviceInformation::class,
						$parsedMessage,
					);

					$promise->resolve($information);
				})
				->otherwise(static function (Throwable $ex) use ($promise): void {
					$promise->reject($ex);
				});
		} else {
			throw new Exceptions\InvalidState('Request promise could not be created');
		}

		return $promise->promise();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getDeviceConfiguration(
		string $address,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::DEVICE_CONFIGURATION_ENDPOINT, $address),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
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

					$promise->resolve(new Entities\API\Gen2\DeviceConfiguration(
						$switches,
						$covers,
						$inputs,
						$lights,
						$temperature,
						$humidity,
					));
				})
				->otherwise(static function (Throwable $ex) use ($promise): void {
					$promise->reject($ex);
				});
		} else {
			throw new Exceptions\InvalidState('Request promise could not be created');
		}

		return $promise->promise();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getDeviceStatus(
		string $address,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::DEVICE_STATUS_ENDPOINT, $address),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					$parsedMessage = $this->schemaValidator->validate(
						$response->getBody()->getContents(),
						$this->getSchemaFilePath(self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME),
					);

					$switches = $covers = $lights = $inputs = [];
					$temperature = $humidity = null;

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
							}
						}
					}

					$promise->resolve(new Entities\API\Gen2\DeviceStatus(
						$switches,
						$covers,
						$inputs,
						$lights,
						$temperature,
						$humidity,
					));
				})
				->otherwise(static function (Throwable $ex) use ($promise): void {
					$promise->reject($ex);
				});
		} else {
			throw new Exceptions\InvalidState('Request promise could not be created');
		}

		return $promise->promise();
	}

	/**
	 * @param array<string, string|int|float|bool> $params
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function setDeviceStatus(
		string $address,
		string $method,
		array $params,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$promise = new Promise\Deferred();

		try {
			$body = Utils\Json::encode([
				'id' => 1,
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

		$result = $this->callRequest(
			'POST',
			sprintf(
				self::DEVICE_ACTION_ENDPOINT,
				$address,
			),
			[],
			$body,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function () use ($promise): void {
					$promise->resolve();
				})
				->otherwise(static function (Throwable $ex) use ($promise): void {
					$promise->reject($ex);
				});
		} else {
			throw new Exceptions\InvalidState('Request promise could not be created');
		}

		return $promise->promise();
	}

}
