<?php declare(strict_types = 1);

/**
 * Local.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          0.13.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Consumers;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Connector\Tuya\Writers;
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
use React\Promise;
use Throwable;
use function array_key_exists;
use function array_map;
use function assert;
use function in_array;
use function intval;
use function is_array;
use function strval;

/**
 * Local devices client
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Local implements Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const RECONNECT_COOL_DOWN_TIME = 300.0;

	private const CMD_STATUS = 'status';

	/** @var array<string, API\LocalApi> */
	private array $devicesClients = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|false>> */
	private array $processedDevicesCommands = [];

	/** @var array<string, DateTimeInterface> */
	private array $devicesLastMessage = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\TuyaConnector $connector,
		private readonly Helpers\Device $deviceHelper,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly Consumers\Messages $consumer,
		private readonly API\LocalApiFactory $localApiFactory,
		private readonly Writers\Writer $writer,
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(): void
	{
		$this->processedDevices = [];

		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\TuyaDevice);

			if ($device->getGateway() === false) {
				$this->createDeviceClient($device);
			}
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);

		$this->writer->connect($this->connector, $this);
	}

	public function disconnect(): void
	{
		foreach ($this->devicesClients as $client) {
			$client->disconnect();
		}

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		$this->writer->disconnect();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\TuyaDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$client = $this->getDeviceClient($device);

		if ($client === null) {
			return Promise\reject(new Exceptions\InvalidArgument('For provided device is not created client'));
		}

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

		if ($state->isPending() === true) {
			$client->writeState(
				$property->getIdentifier(),
				$expectedValue,
				$device->getGateway() instanceof Entities\TuyaDevice ? $device->getIdentifier() : null,
			)
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
				->otherwise(function (Throwable $ex) use ($device, $property, $deferred): void {
					$this->logger->error(
						'Could not call local device api',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'local-client',
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
						],
					);

					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::EXPECTED_VALUE_KEY => null,
							DevicesStates\Property::PENDING_KEY => false,
						]),
					);

					$this->setDeviceState($device, false);

					$deferred->reject($ex);
				});

		} else {
			return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
		}

		return $deferred->promise();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	private function handleCommunication(): void
	{
		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\TuyaDevice);

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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processDevice(Entities\TuyaDevice $device): bool
	{
		$client = $this->getDeviceClient($device);

		if ($client === null) {
			if ($device->getGateway() === false) {
				$this->createDeviceClient($device);
			}

			return true;
		}

		if (array_key_exists($device->getIdentifier(), $this->devicesLastMessage)) {
			$lastMessageTimestamp = $this->devicesLastMessage[$device->getIdentifier()];

			$statusReadingDelay = intval($this->deviceHelper->getConfiguration(
				$device,
				Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_STATUS_READING_DELAY),
			));

			if (
				$this->dateTimeFactory->getNow()->getTimestamp() - $lastMessageTimestamp->getTimestamp() >= $statusReadingDelay + 1
			) {
				$this->setDeviceState($device, false);

				if ($device->getGateway() === false) {
					$client->disconnect();

					unset($this->processedDevicesCommands[$device->getIdentifier()]);

					foreach ($device->getChildren() as $child) {
						unset($this->processedDevicesCommands[$child->getIdentifier()]);
					}
				}
			}
		}

		if (!$client->isConnected()) {
			if (!$client->isConnecting()) {
				if (
					$client->getLastConnectAttempt() === null
					||
						($this->dateTimeFactory->getNow()->getTimestamp() - $client->getLastConnectAttempt()->getTimestamp())
						>= self::RECONNECT_COOL_DOWN_TIME
				) {
					unset($this->processedDevicesCommands[$device->getIdentifier()]);

					$client->connect();

				} else {
					$this->setDeviceState($device, false);
				}
			}

			return true;
		}

		if (
			!$this->deviceConnectionManager->getState($device)->equalsValue(
				MetadataTypes\ConnectionState::STATE_CONNECTED,
			)
		) {
			if ($device->getGateway() === false) {
				return true;
			}
		}

		return $this->readDeviceData(self::CMD_STATUS, $device);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function readDeviceData(
		string $cmd,
		Entities\TuyaDevice $device,
	): bool
	{
		$client = $this->getDeviceClient($device);

		if ($client === null) {
			throw new Exceptions\InvalidState('Device client is not created');
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

		$this->processedDevicesCommands[$device->getIdentifier()][$cmd] = $this->dateTimeFactory->getNow();

		if ($cmd === self::CMD_STATUS) {
			$client->readStates($device->getGateway() instanceof Entities\TuyaDevice ? $device->getIdentifier() : null)
				->then(function (array|Types\LocalDeviceError|null $statuses) use ($cmd, $device): void {
					$this->processedDevicesCommands[$device->getIdentifier()][$cmd] = $this->dateTimeFactory->getNow();
					$this->devicesLastMessage[$device->getIdentifier()] = $this->dateTimeFactory->getNow();

					$excludeDps = [$this->deviceHelper->getConfiguration(
						$device,
						Types\DevicePropertyIdentifier::get(
							Types\DevicePropertyIdentifier::IDENTIFIER_READ_STATE_EXCLUDE_DPS,
						),
					)];

					if (is_array($statuses)) {
						$dataPointsStatuses = [];

						foreach ($statuses as $status) {
							if (!in_array($status->getCode(), $excludeDps, true)) {
								$dataPointsStatuses[] = new Entities\Messages\DataPointStatus(
									Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
									$status->getCode(),
									$status->getValue(),
								);
							}
						}

						$this->consumer->append(new Entities\Messages\DeviceStatus(
							Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
							$this->connector->getId(),
							$device->getIdentifier(),
							$dataPointsStatuses,
						));
					}

					$this->consumer->append(new Entities\Messages\DeviceState(
						Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
						$this->connector->getId(),
						$device->getIdentifier(),
						true,
					));
				})
				->otherwise(function (Throwable $ex) use ($cmd, $device): void {
					if ($ex instanceof Exceptions\LocalApiBusy) {
						$this->processedDevicesCommands[$device->getIdentifier()][$cmd] = false;

					} else {
						$this->logger->warning(
							'Could not call local api',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'local-client',
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
							],
						);

						$this->setDeviceState($device, false);
					}
				});
		}

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createDeviceClient(Entities\TuyaDevice $device): void
	{
		unset($this->processedDevicesCommands[$device->getIdentifier()]);

		$nodeId = $this->deviceHelper->getConfiguration(
			$device,
			Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_NODE_ID),
		);

		$gatewayId = $this->deviceHelper->getConfiguration(
			$device,
			Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_GATEWAY_ID),
		);

		if ($gatewayId !== null || $nodeId !== null) {
			return;
		}

		$client = $this->localApiFactory->create(
			$device->getIdentifier(),
			null,
			null,
			strval($this->deviceHelper->getConfiguration(
				$device,
				Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_LOCAL_KEY),
			)),
			strval($this->deviceHelper->getConfiguration(
				$device,
				Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
			)),
			Types\DeviceProtocolVersion::get(strval($this->deviceHelper->getConfiguration(
				$device,
				Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION),
			))),
			array_map(function (DevicesEntities\Devices\Device $child): Entities\Clients\LocalChild {
				assert($child instanceof Entities\TuyaDevice);

				return new Entities\Clients\LocalChild(
					$child->getIdentifier(),
					strval($this->deviceHelper->getConfiguration(
						$child,
						Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_NODE_ID),
					)),
					Types\LocalDeviceType::get(Types\LocalDeviceType::ZIGBEE),
				);
			}, $device->getChildren()),
		);

		$client->on(
			'message',
			function (Entities\API\Entity $message): void {
				if (
					$message instanceof Entities\API\DeviceRawMessage
					&& $message->getCommand()->equalsValue(Types\LocalDeviceCommand::CMD_STATUS)
					&& is_array($message->getData())
				) {
					$dataPointsStatuses = [];

					foreach ($message->getData() as $entity) {
						if ($entity instanceof Entities\API\DeviceDataPointStatus) {
							$dataPointsStatuses[] = new Entities\Messages\DataPointStatus(
								Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
								$entity->getCode(),
								$entity->getValue(),
							);
						}
					}

					$this->consumer->append(new Entities\Messages\DeviceStatus(
						Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
						$this->connector->getId(),
						$message->getIdentifier(),
						$dataPointsStatuses,
					));
				}
			},
		);

		$client->on(
			'connected',
			function () use ($device): void {
				$this->logger->debug(
					'Connected to device',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'local-client',
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				$this->setDeviceState($device, true);
			},
		);

		$client->on(
			'error',
			function (Throwable $ex) use ($device): void {
				$this->logger->warning(
					'Connection with device failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'local-client',
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
					],
				);

				$this->setDeviceState($device, false);
			},
		);

		$this->devicesClients[$device->getPlainId()] = $client;
	}

	private function getDeviceClient(Entities\TuyaDevice $device): API\LocalApi|null
	{
		$parent = $device->getGateway();

		if ($parent instanceof Entities\TuyaDevice) {
			return array_key_exists(
				$parent->getPlainId(),
				$this->devicesClients,
			)
				? $this->devicesClients[$parent->getPlainId()]
				: null;
		}

		return array_key_exists(
			$device->getPlainId(),
			$this->devicesClients,
		)
			? $this->devicesClients[$device->getPlainId()]
			: null;
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

	private function setDeviceState(Entities\TuyaDevice $device, bool $state): void
	{
		$this->consumer->append(new Entities\Messages\DeviceState(
			Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
			$this->connector->getId(),
			$device->getIdentifier(),
			$state,
		));

		foreach ($device->getChildren() as $child) {
			$this->consumer->append(new Entities\Messages\DeviceState(
				Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
				$this->connector->getId(),
				$child->getIdentifier(),
				$state,
			));
		}
	}

}
