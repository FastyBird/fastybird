<?php declare(strict_types = 1);

/**
 * Http.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Clients\Local;

use DateTimeInterface;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Clients;
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
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use React\Http as ReactHttp;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function assert;
use function in_array;
use function intval;
use function is_string;
use function preg_match;
use function strval;

/**
 * HTTP api client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Http implements Clients\Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const CMD_STATUS = 'status';

	private const GEN1_CHANNEL_BLOCK = '/^(?P<identifier>[0-9]+)_(?P<description>[a-zA-Z]+)(_(?P<channel>[0-9]+))?$/';

	private const GEN1_PROPERTY_SENSOR = '/^(?P<identifier>[0-9]+)_(?P<type>[a-zA-Z]{1,3})_(?P<description>[a-zA-Z0-9]+)$/';

	private const GEN2_PROPERTY_COMPONENT = '/^(?P<component>[a-zA-Z]+)_(?P<identifier>[0-9]+)(_(?P<attribute>[a-zA-Z0-9]+))?$/';

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private API\Gen1HttpApi|null $gen1httpApi = null;

	private API\Gen2HttpApi|null $gen2httpApi = null;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly API\Gen1Transformer $transformer,
		private readonly API\Gen1HttpApiFactory $gen1HttpApiFactory,
		private readonly API\Gen2HttpApiFactory $gen2HttpApiFactory,
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

	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->handlerTimer = null;

		$this->gen1httpApi = $this->gen1HttpApiFactory->create();
		$this->gen2httpApi = $this->gen2HttpApiFactory->create();

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

			$this->handlerTimer = null;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\ShellyDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$state = $this->channelPropertiesStates->getValue($property);

		if ($state === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property state could not be found. Nothing to write'),
			);
		}

		$expectedValue = DevicesUtilities\ValueHelper::flattenValue($state->getExpectedValue());

		if (!$property->isSettable()) {
			return Promise\reject(new Exceptions\InvalidArgument('Provided property is not writable'));
		}

		if ($expectedValue === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property expected value is not set. Nothing to write'),
			);
		}

		$valueToWrite = $this->transformer->transformValueToDevice(
			$property->getDataType(),
			$property->getFormat(),
			$state->getExpectedValue(),
		);

		if ($valueToWrite === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property expected value could not be transformed to device'),
			);
		}

		if ($state->isPending() === true) {
			$this->writeSensor($device, $channel, $property, $valueToWrite)
				->then(function () use ($property, $deferred): void {
					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::PENDING_KEY => $this->dateTimeFactory->getNow()->format(
								DateTimeInterface::ATOM,
							),
						]),
					);

					$deferred->resolve();
				})
				->otherwise(function (Throwable $ex) use ($device, $channel, $property, $deferred): void {
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

						$deferred->reject($ex);
					}
				});

		} else {
			return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
		}

		return $deferred->promise();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	private function handleCommunication(): void
	{
		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\ShellyDevice);

			if (
				!in_array($device->getPlainId(), $this->processedDevices, true)
				&& !$this->deviceConnectionManager->getState($device)->equalsValue(
					MetadataTypes\ConnectionState::STATE_STOPPED,
				)
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
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	private function processDevice(Entities\ShellyDevice $device): bool
	{
		return $this->readDeviceData(self::CMD_STATUS, $device);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	private function readDeviceData(string $cmd, Entities\ShellyDevice $device): bool
	{
		$address = $this->buildDeviceAddress($device);

		if ($address === null) {
			return false;
		}

		$cmdResult = null;

		if (!array_key_exists($device->getIdentifier(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getIdentifier()] = [];
		}

		if (array_key_exists($cmd, $this->processedDevicesCommands[$device->getIdentifier()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getIdentifier()][$cmd];
		}

		$delay = null;

		if ($cmd === self::CMD_STATUS) {
			$delay = $device->getStatusReadingDelay();
		}

		if (
			$delay === null && $cmdResult === null
			|| (
				$cmdResult instanceof DateTimeInterface
				&& ($this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp()) < $delay
			)
		) {
			return false;
		}

		$generation = $device->getGeneration();

		$this->processedDevicesCommands[$device->getIdentifier()][$cmd] = $this->dateTimeFactory->getNow();

		if ($cmd === self::CMD_STATUS) {
			if ($generation->equalsValue(Types\DeviceGeneration::GENERATION_1)) {
				$result = $this->gen1httpApi?->getDeviceStatus($address);
				assert($result instanceof Promise\PromiseInterface);

			} elseif ($generation->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
				$result = $this->gen2httpApi?->getDeviceStatus($address);
				assert($result instanceof Promise\PromiseInterface);

			} else {
				return false;
			}

			$result
				->then(
					function (Entities\API\Gen1\DeviceStatus|Entities\API\Gen2\DeviceStatus $status) use ($cmd, $device): void {
						if ($status instanceof Entities\API\Gen1\DeviceStatus) {
							$this->processGen1DeviceStatus($device, $status);
						} else {
							$this->processGen2DeviceStatus($device, $status);
						}

						$this->processedDevicesCommands[$device->getIdentifier()][$cmd] = $this->dateTimeFactory->getNow();
					},
				)
				->otherwise(function (Throwable $ex) use ($device): void {
					if ($ex instanceof ReactHttp\Message\ResponseException) {
						if (
							$ex->getCode() >= StatusCodeInterface::STATUS_BAD_REQUEST
							&& $ex->getCode() < StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS
						) {
							$this->deviceConnectionManager->setState(
								$device,
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
							);

						} elseif (
							$ex->getCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
							&& $ex->getCode() < StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED
						) {
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
				});
		}

		return true;
	}

	/**
	 * @throws Exceptions\InvalidState
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
		$deferred = new Promise\Deferred();

		$address = $this->buildDeviceAddress($device);

		if ($address === null) {
			return Promise\reject(new Exceptions\InvalidState('Device address could not be determined'));
		}

		$generation = $device->getGeneration();

		if ($generation->equalsValue(Types\DeviceGeneration::GENERATION_1)) {
			if (
				preg_match(self::GEN1_CHANNEL_BLOCK, $channel->getIdentifier(), $channelMatches) !== 1
				|| !array_key_exists('identifier', $channelMatches)
				|| !array_key_exists('description', $channelMatches)
				|| !array_key_exists('channel', $channelMatches)
			) {
				return Promise\reject(new Exceptions\InvalidState('Channel identifier is not in expected format'));
			}

			try {
				$sensorAction = $this->buildGen1SensorAction($property->getIdentifier());

			} catch (Exceptions\InvalidState) {
				return Promise\reject(new Exceptions\InvalidState('Sensor action could not be created'));
			}

			$result = $this->gen1httpApi?->setDeviceStatus(
				$address,
				$channelMatches['description'],
				intval($channelMatches['channel']),
				$sensorAction,
				$valueToWrite,
			);
			assert($result instanceof Promise\PromiseInterface);

		} elseif ($generation->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
			if (
				preg_match(self::GEN2_PROPERTY_COMPONENT, $property->getIdentifier(), $propertyMatches) !== 1
				|| !array_key_exists('component', $propertyMatches)
				|| !array_key_exists('identifier', $propertyMatches)
				|| !array_key_exists('attribute', $propertyMatches)
			) {
				return Promise\reject(new Exceptions\InvalidState('Property identifier is not in expected format'));
			}

			try {
				$componentMethod = $this->buildGen2ComponentMethod($property->getIdentifier());

			} catch (Exceptions\InvalidState) {
				return Promise\reject(new Exceptions\InvalidState('Sensor action could not be created'));
			}

			$result = $this->gen2httpApi?->setDeviceStatus(
				$address,
				$componentMethod,
				[
					'id' => intval($propertyMatches['identifier']),
					$propertyMatches['attribute'] => $valueToWrite,
				],
			);
			assert($result instanceof Promise\PromiseInterface);

		} else {
			return Promise\reject(new Exceptions\InvalidState('Device is in unsupported generation'));
		}

		$result
			->then(
				static function () use ($deferred): void {
					$deferred->resolve();
				},
			)
			->otherwise(function (Throwable $ex) use ($device, $channel, $property, $deferred): void {
				$this->logger->error(
					'Failed to call device http api',
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

				$deferred->reject($ex);
			});

		$promise = $deferred->promise();
		assert($promise instanceof Promise\ExtendedPromiseInterface);

		return $promise;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function buildDeviceAddress(Entities\ShellyDevice $device): string|null
	{
		$ipAddress = $device->getIpAddress();

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

		$username = $device->getUsername();
		$password = $device->getPassword();

		if (is_string($username) && is_string($password)) {
			return $username . ':' . $password . '@' . $ipAddress;
		}

		return $ipAddress;
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

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildGen1SensorAction(string $property): string
	{
		if (preg_match(self::GEN1_PROPERTY_SENSOR, $property, $propertyMatches) !== 1) {
			throw new Exceptions\InvalidState('Property identifier is not valid');
		}

		if (
			!array_key_exists('identifier', $propertyMatches)
			|| !array_key_exists('type', $propertyMatches)
			|| !array_key_exists('description', $propertyMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not valid');
		}

		if ($propertyMatches['description'] === Types\SensorDescription::DESC_OUTPUT) {
			return 'turn';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::DESC_ROLLER) {
			return 'go';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::DESC_COLOR_TEMP) {
			return 'temp';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::DESC_WHITE_LEVEL) {
			return 'white';
		}

		return $propertyMatches['description'];
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildGen2ComponentMethod(string $property): string
	{
		if (preg_match(self::GEN2_PROPERTY_COMPONENT, $property, $propertyMatches) !== 1) {
			throw new Exceptions\InvalidState('Property identifier is not valid');
		}

		if (
			!array_key_exists('component', $propertyMatches)
			|| !array_key_exists('identifier', $propertyMatches)
			|| !array_key_exists('attribute', $propertyMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not valid');
		}

		if (
			$propertyMatches['component'] === Types\ComponentType::TYPE_SWITCH
			&& $propertyMatches['description'] === Types\ComponentAttributeType::ATTRIBUTE_ON
		) {
			return 'Switch.Set';
		}

		if (
			$propertyMatches['component'] === Types\ComponentType::TYPE_COVER
			&& $propertyMatches['description'] === Types\ComponentAttributeType::ATTRIBUTE_POSITION
		) {
			return 'Cover.GoToPosition';
		}

		if (
			$propertyMatches['component'] === Types\ComponentType::TYPE_LIGHT
			&& (
				$propertyMatches['description'] === Types\ComponentAttributeType::ATTRIBUTE_ON
				|| $propertyMatches['description'] === Types\ComponentAttributeType::ATTRIBUTE_BRIGHTNESS
			)
		) {
			return 'Light.Set';
		}

		throw new Exceptions\InvalidState('Property method could not be build');
	}

	public function findProperty(
		Entities\ShellyDevice $device,
		string $propertyIdentifier,
	): DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic|null
	{
		$property = $device->findProperty($propertyIdentifier);

		if ($property instanceof DevicesEntities\Devices\Properties\Dynamic) {
			return $property;
		}

		foreach ($device->getChannels() as $channel) {
			$property = $channel->findProperty($propertyIdentifier);

			if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
				return $property;
			}
		}

		return null;
	}

	/**
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function processGen1DeviceStatus(
		Entities\ShellyDevice $device,
		Entities\API\Gen1\DeviceStatus $status,
	): void
	{
		$source = Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_HTTP);

		$statuses = [];

		foreach ($status->getInputs() as $index => $input) {
			foreach ($device->getChannels() as $channel) {
				if (Utils\Strings::endsWith($channel->getIdentifier(), '_' . $index)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_INPUT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getInput(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_INPUT_EVENT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getEvent(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_INPUT_EVENT_COUNT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getEventCnt(),
								),
							);
						}
					}

					$statuses[] = new Entities\Messages\ChannelStatus(
						$source,
						$channel->getId(),
						$result,
					);

					break;
				}
			}
		}

		foreach ($status->getRelays() as $index => $relay) {
			foreach ($device->getChannels() as $channel) {
				if (Utils\Strings::endsWith($channel->getIdentifier(), 'relay_' . $index)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_OUTPUT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->getState(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_OVERPOWER,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->hasOverpower(),
								),
							);
						}
					}

					$statuses[] = new Entities\Messages\ChannelStatus(
						$source,
						$channel->getId(),
						$result,
					);
				} elseif (Utils\Strings::endsWith($channel->getIdentifier(), 'device')) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_OVERTEMPERATURE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->hasOvertemperature(),
								),
							);
						}
					}

					$statuses[] = new Entities\Messages\ChannelStatus(
						$source,
						$channel->getId(),
						$result,
					);
				}
			}
		}

		foreach ($status->getRollers() as $index => $roller) {
			foreach ($device->getChannels() as $channel) {
				if (Utils\Strings::endsWith($channel->getIdentifier(), 'roller_' . $index)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_ROLLER,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getState(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_ROLLER_POSITION,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getCurrentPosition(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_ROLLER_STOP_REASON,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getStopReason(),
								),
							);
						}
					}

					$statuses[] = new Entities\Messages\ChannelStatus(
						$source,
						$channel->getId(),
						$result,
					);
				} elseif (Utils\Strings::endsWith($channel->getIdentifier(), 'device')) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_OVERTEMPERATURE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->hasOvertemperature(),
								),
							);
						}
					}

					$statuses[] = new Entities\Messages\ChannelStatus(
						$source,
						$channel->getId(),
						$result,
					);
				}
			}
		}

		foreach ($status->getLights() as $index => $light) {
			foreach ($device->getChannels() as $channel) {
				if (Utils\Strings::endsWith($channel->getIdentifier(), 'light_' . $index)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_RED,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getRed(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_GREEN,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getGreen(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_BLUE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getBlue(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_GAIN,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getGain(),
								),
							);
						} elseif (
							Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::DESC_WHITE,
							)
							|| Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::DESC_WHITE_LEVEL,
							)
						) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getWhite(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_EFFECT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getEffect(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_BRIGHTNESS,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getBrightness(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_OUTPUT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$source,
								$property->getIdentifier(),
								$this->transformer->transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getState(),
								),
							);
						}
					}

					$statuses[] = new Entities\Messages\ChannelStatus(
						$source,
						$channel->getId(),
						$result,
					);

					break;
				}
			}
		}

		$this->consumer->append(
			new Entities\Messages\DeviceStatus(
				$source,
				$this->connector->getId(),
				$device->getIdentifier(),
				$statuses,
			),
		);
	}

	private function processGen2DeviceStatus(
		Entities\ShellyDevice $device,
		Entities\API\Gen2\DeviceStatus $status,
	): void
	{
		$source = Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_2_HTTP);

		$statuses = array_map(
			function ($component) use ($device, $source): array {
				$result = [];

				if ($component instanceof Entities\API\Gen2\DeviceSwitchStatus) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_ON
						),
					);

					if ($property !== null) {
						$result[] = new Entities\Messages\PropertyStatus(
							$source,
							$property->getIdentifier(),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getOutput(),
							),
						);
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceCoverStatus) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
						),
					);

					if ($property !== null) {
						$result[] = new Entities\Messages\PropertyStatus(
							$source,
							$property->getIdentifier(),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getState() !== null ? strval(
									$component->getState()->getValue(),
								) : null,
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_POSITION
						),
					);

					if ($property !== null) {
						$result[] = new Entities\Messages\PropertyStatus(
							$source,
							$property->getIdentifier(),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getCurrentPosition(),
							),
						);
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceLightStatus) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_ON
						),
					);

					if ($property !== null) {
						$result[] = new Entities\Messages\PropertyStatus(
							$source,
							$property->getIdentifier(),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getOutput(),
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_BRIGHTNESS
						),
					);

					if ($property !== null) {
						$result[] = new Entities\Messages\PropertyStatus(
							$source,
							$property->getIdentifier(),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getBrightness(),
							),
						);
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceInputStatus) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
						),
					);

					if ($property !== null) {
						if ($component->getState() instanceof Types\InputPayload) {
							$value = strval($component->getState()->getValue());
						} elseif ($component->getState() !== null) {
							$value = $component->getState();
						} else {
							$value = $component->getPercent();
						}

						$result[] = new Entities\Messages\PropertyStatus(
							$source,
							$property->getIdentifier(),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$value,
							),
						);
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceTemperatureStatus) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_CELSIUS
						),
					);

					if ($property !== null) {
						$result[] = new Entities\Messages\PropertyStatus(
							$source,
							$property->getIdentifier(),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperatureCelsius(),
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_FAHRENHEIT
						),
					);

					if ($property !== null) {
						$result[] = new Entities\Messages\PropertyStatus(
							$source,
							$property->getIdentifier(),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperatureFahrenheit(),
							),
						);
					}
				} else {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
						),
					);

					if ($property !== null) {
						$result[] = new Entities\Messages\PropertyStatus(
							$source,
							$property->getIdentifier(),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getRelativeHumidity(),
							),
						);
					}
				}

				return $result;
			},
			array_merge(
				$status->getSwitches(),
				$status->getCovers(),
				$status->getInputs(),
				$status->getLights(),
				$status->getTemperature() !== null ? [$status->getTemperature()] : [],
				$status->getHumidity() !== null ? [$status->getHumidity()] : [],
			),
		);

		$statuses = array_filter($statuses, static fn (array $item): bool => $item !== []);
		$statuses = array_merge([], ...$statuses);

		$this->consumer->append(
			new Entities\Messages\DeviceStatus(
				$source,
				$this->connector->getId(),
				$device->getIdentifier(),
				$statuses,
			),
		);
	}

}
