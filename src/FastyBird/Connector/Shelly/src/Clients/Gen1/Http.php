<?php declare(strict_types = 1);

/**
 * Http.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.37.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Clients\Gen1;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Consumers;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use React\EventLoop;
use React\Http as ReactHttp;
use React\Promise;
use Throwable;
use function array_key_exists;
use function assert;
use function in_array;
use function is_string;
use function preg_match;

/**
 * HTTP api client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Http
{

	use Nette\SmartObject;

	private const SHELLY_ENDPOINT = 'http://ADDRESS/shelly';

	// private const STATUS_ENDPOINT = 'http://ADDRESS/status';
	// private const SETTINGS_ENDPOINT = 'http://ADDRESS/settings';
	private const DESCRIPTION_ENDPOINT = 'http://ADDRESS/cit/d';

	private const SET_CHANNEL_SENSOR_ENDPOINT = 'http://ADDRESS/CHANNEL/INDEX?ACTION=VALUE';

	private const CHANNEL_BLOCK = '/^(?P<identifier>[0-9]+)_(?P<description>[a-zA-Z0-9_]+)$/';

	private const PROPERTY_SENSOR = '/^(?P<identifier>[0-9]+)_(?P<type>[a-zA-Z]{1,3})_(?P<description>[a-zA-Z0-9]+)$/';

	private const BLOCK_PARTS = '/^(?P<channelName>[a-zA-Z]+)_(?P<channelIndex>[0-9_]+)$/';

	private const CMD_SHELLY = 'shelly';

	// private const CMD_SETTINGS = 'settings';
	private const CMD_DESCRIPTION = 'description';

	// private const CMD_STATUS = 'status';

	private const SENDING_CMD_DELAY = 120;

	private const HANDLER_START_DELAY = 2;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	/** @var array<string, DateTimeInterface> */
	private array $processedProperties = [];

	private EventLoop\TimerInterface|null $handlerTimer;

	private ReactHttp\Browser|null $browser = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly API\Gen1Validator $validator,
		private readonly API\Gen1Parser $parser,
		private readonly API\Gen1Transformer $transformer,
		private readonly Helpers\Device $deviceHelper,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly Consumers\Messages $consumer,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function connect(): void
	{
		$this->browser = new ReactHttp\Browser($this->eventLoop);

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	private function handleCommunication(): void
	{
		foreach ($this->processedProperties as $index => $processedProperty) {
			if ((float) $this->dateTimeFactory->getNow()->format('Uv') - (float) $processedProperty->format(
				'Uv',
			) >= 500) {
				unset($this->processedProperties[$index]);
			}
		}

		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\ShellyDevice);

			$ipAddress = $this->deviceHelper->getConfiguration(
				$device,
				Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
			);

			if (
				!in_array($device->getPlainId(), $this->processedDevices, true)
				&& is_string($ipAddress)
				&& !$this->deviceConnectionManager->getState($device)
					->equalsValue(MetadataTypes\ConnectionState::STATE_STOPPED)
			) {
				$this->processedDevices[] = $device->getPlainId();

				if ($this->processDevice($device)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	private function processDevice(Entities\ShellyDevice $device): bool
	{
		if ($this->readDeviceData(self::CMD_SHELLY, $device)) {
			return true;
		}

		if ($this->readDeviceData(self::CMD_DESCRIPTION, $device)) {
			return true;
		}

		if (
			$this->deviceConnectionManager->getState($device)
				->equalsValue(MetadataTypes\ConnectionState::STATE_CONNECTED)
		) {
			return $this->writeChannelsProperty($device);
		}

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	private function readDeviceData(string $cmd, Entities\ShellyDevice $device): bool
	{
		$httpCmdResult = null;

		if (!array_key_exists($device->getPlainId(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getPlainId()] = [];
		}

		if (array_key_exists($cmd, $this->processedDevicesCommands[$device->getPlainId()])) {
			$httpCmdResult = $this->processedDevicesCommands[$device->getPlainId()][$cmd];
		}

		if ($httpCmdResult === true) {
			return false;
		}

		if (
			$httpCmdResult instanceof DateTimeInterface
			&& ($this->dateTimeFactory->getNow()->getTimestamp() - $httpCmdResult->getTimestamp()) < self::SENDING_CMD_DELAY
		) {
			return true;
		}

		$result = null;

		if ($cmd === self::CMD_SHELLY) {
			$result = $this->readDeviceInfo($device);

		} elseif ($cmd === self::CMD_DESCRIPTION) {
			$result = $this->readDeviceDescription($device);
		}

		$this->processedDevicesCommands[$device->getPlainId()][$cmd] = $this->dateTimeFactory->getNow();

		$result
			?->then(function () use ($cmd, $device): void {
				$this->processedDevicesCommands[$device->getPlainId()][$cmd] = true;
			})
			->otherwise(function (Throwable $ex) use ($cmd, $device): void {
				if ($ex instanceof ReactHttp\Message\ResponseException) {
					if ($ex->getCode() >= 400 && $ex->getCode() < 499) {
						$this->deviceConnectionManager->setState(
							$device,
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
						);

					} elseif ($ex->getCode() >= 500 && $ex->getCode() < 599) {
						$this->deviceConnectionManager->setState(
							$device,
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
						);

					} else {
						$this->deviceConnectionManager->setState(
							$device,
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN),
						);
					}
				}

				if ($ex instanceof Exceptions\Runtime) {
					$this->deviceConnectionManager->setState(
						$device,
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
					);
				}

				$this->processedDevicesCommands[$device->getPlainId()][$cmd] = $this->dateTimeFactory->getNow();
			});

		return true;
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	private function writeChannelsProperty(Entities\ShellyDevice $device): bool
	{
		$now = $this->dateTimeFactory->getNow();

		foreach ($device->getChannels() as $channel) {
			foreach ($channel->getProperties() as $property) {
				if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
					continue;
				}

				$state = $this->channelPropertiesStates->getValue($property);

				if ($state === null) {
					continue;
				}

				if (
					$property->isSettable()
					&& $state->getExpectedValue() !== null
					&& $state->isPending() === true
				) {
					$debounce = array_key_exists(
						$property->getPlainId(),
						$this->processedProperties,
					)
						? $this->processedProperties[$property->getPlainId()]
						: false;

					if (
						$debounce !== false
						&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < 500
					) {
						continue;
					}

					unset($this->processedProperties[$property->getPlainId()]);

					$pending = $state->getPending();

					if (
						$pending === true
						|| (
							$pending instanceof DateTimeInterface
							&& (float) $now->format('Uv') - (float) $pending->format('Uv') > 2_000
						)
					) {
						$this->processedProperties[$property->getPlainId()] = $now;

						$valueToWrite = $this->transformer->transformValueToDevice(
							$property->getDataType(),
							$property->getFormat(),
							$state->getExpectedValue(),
						);

						if ($valueToWrite === null) {
							return false;
						}

						$this->writeSensor(
							$device,
							$channel,
							$property,
							$valueToWrite,
						)
							->then(function () use ($property): void {
								$this->propertyStateHelper->setValue(
									$property,
									Utils\ArrayHash::from([
										DevicesStates\Property::PENDING_KEY => $this->dateTimeFactory->getNow()->format(
											DateTimeInterface::ATOM,
										),
									]),
								);
							})
							->otherwise(function (Throwable $ex) use ($device, $channel, $property): void {
								if ($ex instanceof ReactHttp\Message\ResponseException) {
									if ($ex->getCode() >= 400 && $ex->getCode() < 499) {
										$this->propertyStateHelper->setValue(
											$property,
											Utils\ArrayHash::from([
												DevicesStates\Property::EXPECTED_VALUE_KEY => null,
												DevicesStates\Property::PENDING_KEY => false,
											]),
										);

										$this->logger->warning(
											'Expected value could not be set',
											[
												'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
												'type' => 'http-client',
												'exception' => [
													'message' => $ex->getMessage(),
													'code' => $ex->getCode(),
												],
												'connector' => [
													'id' => $this->connector->getPlainId(),
												],
												'device' => [
													'id' => $device->getPlainId(),
												],
												'channel' => [
													'id' => $channel->getPlainId(),
												],
												'property' => [
													'id' => $property->getPlainId(),
												],
											],
										);

									} elseif ($ex->getCode() >= 500 && $ex->getCode() < 599) {
										$this->deviceConnectionManager->setState(
											$device,
											MetadataTypes\ConnectionState::get(
												MetadataTypes\ConnectionState::STATE_LOST,
											),
										);
									}
								}

								unset($this->processedProperties[$property->getPlainId()]);
							});

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	private function readDeviceInfo(
		Entities\ShellyDevice $device,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$address = $this->buildDeviceAddress($device);

		if ($address === null) {
			return Promise\reject(new Exceptions\InvalidState('Device address could not be determined'));
		}

		return $this->getBrowser()->get(
			Utils\Strings::replace(
				self::SHELLY_ENDPOINT,
				[
					'/ADDRESS/' => $address,
				],
			),
		)
			->then(function (Message\ResponseInterface $response) use ($device, $address): void {
				$message = $response->getBody()->getContents();

				if ($this->validator->isValidHttpShellyMessage($message)) {
					try {
						$this->consumer->append(
							$this->parser->parseHttpShellyMessage(
								$this->connector->getId(),
								$device->getIdentifier(),
								$address,
								$message,
							),
						);
					} catch (Exceptions\ParseMessage $ex) {
						$this->logger->warning(
							'Received message could not be parsed into entity',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
								'type' => 'http-client',
								'exception' => [
									'message' => $ex->getMessage(),
									'code' => $ex->getCode(),
								],
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
							],
						);
					}
				}
			})
			->otherwise(function (Throwable $ex) use ($address, $device): void {
				$this->logger->error(
					'Failed to call device http api',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'http-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'endpoint' => Utils\Strings::replace(
							self::SHELLY_ENDPOINT,
							[
								'/ADDRESS/' => $address,
							],
						),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				throw $ex;
			});
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	private function readDeviceDescription(
		Entities\ShellyDevice $device,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$address = $this->buildDeviceAddress($device);

		if ($address === null) {
			return Promise\reject(new Exceptions\InvalidState('Device address could not be determined'));
		}

		return $this->getBrowser()->get(
			Utils\Strings::replace(
				self::DESCRIPTION_ENDPOINT,
				[
					'/ADDRESS/' => $address,
				],
			),
		)
			->then(function (Message\ResponseInterface $response) use ($device, $address): void {
				$message = $response->getBody()->getContents();

				if ($this->validator->isValidHttpDescriptionMessage($message)) {
					try {
						$this->consumer->append(
							$this->parser->parseHttpDescriptionMessage(
								$this->connector->getId(),
								$device->getIdentifier(),
								$address,
								$message,
							),
						);
					} catch (Exceptions\ParseMessage $ex) {
						$this->logger->warning(
							'Received message could not be parsed into entity',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
								'type' => 'http-client',
								'exception' => [
									'message' => $ex->getMessage(),
									'code' => $ex->getCode(),
								],
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
							],
						);
					}
				}
			})
			->otherwise(function (Throwable $ex) use ($address, $device): void {
				$this->logger->error(
					'Failed to call device http api',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'http-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'endpoint' => Utils\Strings::replace(
							self::DESCRIPTION_ENDPOINT,
							[
								'/ADDRESS/' => $address,
							],
						),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				throw $ex;
			});
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function writeSensor(
		Entities\ShellyDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
		float|bool|int|string $valueToWrite,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$address = $this->buildDeviceAddress($device);

		if ($address === null) {
			return Promise\reject(new Exceptions\InvalidState('Device address could not be determined'));
		}

		if (
			preg_match(self::CHANNEL_BLOCK, $channel->getIdentifier(), $channelMatches) !== 1
			|| !array_key_exists('identifier', $channelMatches)
			|| !array_key_exists('description', $channelMatches)
		) {
			$this->logger->error(
				'Channel identifier is not in expected format',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'http-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
					'device' => [
						'id' => $device->getPlainId(),
					],
					'channel' => [
						'id' => $channel->getPlainId(),
					],
					'property' => [
						'id' => $property->getPlainId(),
					],
				],
			);

			return Promise\reject(new Exceptions\InvalidState('Channel identifier is not in expected format'));
		}

		if (
			preg_match(self::BLOCK_PARTS, $channelMatches['description'], $blockMatches) !== 1
			|| !array_key_exists('channelName', $blockMatches)
			|| !array_key_exists('channelIndex', $blockMatches)
		) {
			$this->logger->error(
				'Channel - block description is not in expected format',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'http-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
					'device' => [
						'id' => $device->getPlainId(),
					],
					'channel' => [
						'id' => $channel->getPlainId(),
					],
					'property' => [
						'id' => $property->getPlainId(),
					],
				],
			);

			return Promise\reject(
				new Exceptions\InvalidState('Channel - block description is not in expected format'),
			);
		}

		try {
			$sensorAction = $this->buildSensorAction($property);

		} catch (Exceptions\InvalidState $ex) {
			$this->logger->error(
				'Sensor action could not be created',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'http-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
					'device' => [
						'id' => $device->getPlainId(),
					],
					'channel' => [
						'id' => $channel->getPlainId(),
					],
					'property' => [
						'id' => $property->getPlainId(),
					],
				],
			);

			return Promise\reject(new Exceptions\InvalidState('Sensor action could not be created'));
		}

		// @phpstan-ignore-next-line
		return $this->getBrowser()->get(
			Utils\Strings::replace(
				self::SET_CHANNEL_SENSOR_ENDPOINT,
				[
					'/ADDRESS/' => $address,
					'/CHANNEL/' => $blockMatches['channelName'],
					'/INDEX/' => $blockMatches['channelIndex'],
					'/ACTION/' => $sensorAction,
					'/VALUE/' => $valueToWrite,
				],
			),
		)
			->otherwise(function (Throwable $ex) use (
				$address,
				$blockMatches,
				$sensorAction,
				$valueToWrite,
				$device,
				$channel,
				$property,
			): void {
				$this->logger->error(
					'Failed to call device http api',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'http-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'endpoint' => Utils\Strings::replace(
							self::SET_CHANNEL_SENSOR_ENDPOINT,
							[
								'/ADDRESS/' => $address,
								'/CHANNEL/' => $blockMatches['channelName'],
								'/INDEX/' => $blockMatches['channelIndex'],
								'/ACTION/' => $sensorAction,
								'/VALUE/' => $valueToWrite,
							],
						),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
						'device' => [
							'id' => $device->getPlainId(),
						],
						'channel' => [
							'id' => $channel->getPlainId(),
						],
						'property' => [
							'id' => $property->getPlainId(),
						],
					],
				);

				throw $ex;
			});
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 * @throws InvalidArgumentException
	 */
	private function getBrowser(): ReactHttp\Browser
	{
		if ($this->browser === null) {
			$this->connect();
		}

		if ($this->browser === null) {
			throw new DevicesExceptions\Terminate('HTTP client could not be established');
		}

		return $this->browser;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function buildDeviceAddress(Entities\ShellyDevice $device): string|null
	{
		$ipAddress = $this->deviceHelper->getConfiguration(
			$device,
			Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
		);

		if (!is_string($ipAddress)) {
			$this->logger->error(
				'Device IP address could not be determined',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'http-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
					'device' => [
						'id' => $device->getPlainId(),
					],
				],
			);

			return null;
		}

		$username = $this->deviceHelper->getConfiguration(
			$device,
			Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_USERNAME),
		);

		$password = $this->deviceHelper->getConfiguration(
			$device,
			Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_PASSWORD),
		);

		if (is_string($username) && is_string($password)) {
			return $username . ':' . $password . '@' . $ipAddress;
		}

		return $ipAddress;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildSensorAction(
		DevicesEntities\Channels\Properties\Dynamic $property,
	): string
	{
		if (preg_match(self::PROPERTY_SENSOR, $property->getIdentifier(), $propertyMatches) !== 1) {
			throw new Exceptions\InvalidState('Property identifier is not valid');
		}

		if (
			!array_key_exists('identifier', $propertyMatches)
			|| !array_key_exists('type', $propertyMatches)
			|| !array_key_exists('description', $propertyMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not valid');
		}

		if ($propertyMatches['description'] === Types\WritableSensorType::TYPE_OUTPUT) {
			return 'turn';
		}

		if ($propertyMatches['description'] === Types\WritableSensorType::TYPE_COLOR_TEMP) {
			return 'temp';
		}

		return $propertyMatches['description'];
	}

	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			function (): void {
				$this->handleCommunication();
			},
		);
	}

}
