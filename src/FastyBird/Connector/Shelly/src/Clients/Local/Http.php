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
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use React\Http as ReactHttp;
use React\Promise;
use Throwable;
use function array_key_exists;
use function assert;
use function in_array;
use function intval;
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
	 */
	private function readDeviceData(string $cmd, Entities\ShellyDevice $device): bool
	{
		$address = $this->buildDeviceAddress($device);

		if ($address === null) {
			// Promise\reject(new Exceptions\InvalidState('Device address could not be determined'));
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
			$delay = intval($this->deviceHelper->getConfiguration(
				$device,
				Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_STATUS_READING_DELAY),
			));
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

		$generation = $this->deviceHelper->getConfiguration(
			$device,
			Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_GENERATION),
		);

		if (!$generation instanceof Types\DeviceGeneration) {
			// Promise\reject(new Exceptions\InvalidState('Device generation could not be determined'));
			return false;
		}

		$this->processedDevicesCommands[$device->getIdentifier()][$cmd] = $this->dateTimeFactory->getNow();

		if ($cmd === self::CMD_STATUS) {
			if ($generation->equalsValue(Types\DeviceGeneration::GENERATION_1)) {
				$result = $this->gen1httpApi?->getDeviceStatus($address);

			} elseif ($generation->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
				$result = $this->gen2httpApi?->getDeviceStatus($address);

			} else {
				return false;
			}

			if ($result === null) {
				return false;
			}

			$result
				->then(
					function (Entities\API\Gen1\DeviceStatus|Entities\API\Gen2\DeviceStatus $status) use ($cmd, $device): void {
						$this->processedDevicesCommands[$device->getIdentifier()][$cmd] = $this->dateTimeFactory->getNow();
					},
				)
				->otherwise(function (Throwable $ex) use ($device): void {
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

		$generation = $this->deviceHelper->getConfiguration(
			$device,
			Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_GENERATION),
		);

		if (!$generation instanceof Types\DeviceGeneration) {
			return Promise\reject(new Exceptions\InvalidState('Device generation could not be determined'));
		}

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

		} else {
			return Promise\reject(new Exceptions\InvalidState('Device is in unsupported generation'));
		}

		if ($result === null) {
			return Promise\reject(new Exceptions\InvalidState('Device is in unsupported generation'));
		}

		$result
			->then(
				function (Entities\API\Gen1\DeviceStatus|Entities\API\Gen2\DeviceStatus $status) use ($device, $deferred): void {
					$this->consumer->append(
						new Entities\Messages\DeviceStatus(
							Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_HTTP),
							$this->connector->getId(),
							$device->getIdentifier(),
							[],
						),
					);

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

		if ($propertyMatches['description'] === Types\SensorDescription::TYPE_OUTPUT) {
			return 'turn';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::TYPE_ROLLER) {
			return 'go';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::TYPE_COLOR_TEMP) {
			return 'temp';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::TYPE_WHITE_LEVEL) {
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

}
