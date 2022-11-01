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
use Throwable;
use function array_key_exists;
use function assert;
use function in_array;
use function is_array;
use function is_string;
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

	private const SENDING_COMMAND_DELAY = 120.0;

	private const RECONNECT_COOL_DOWN_TIME = 300.0;

	private const CMD_STATUS = 'status';

	/** @var Array<string, API\LocalApi> */
	private array $devicesClients = [];

	/** @var Array<string> */
	private array $processedDevices = [];

	/** @var Array<string, Array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	/** @var Array<string, DateTimeInterface> */
	private array $processedProperties = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\TuyaConnector $connector,
		private readonly Helpers\Device $deviceHelper,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly Consumers\Messages $consumer,
		private readonly API\LocalApiFactory $localApiFactory,
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
		$this->processedProperties = [];

		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\TuyaDevice);

			$this->createDeviceClient($device);
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);
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
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	private function processDevice(Entities\TuyaDevice $device): bool
	{
		if (!array_key_exists($device->getPlainId(), $this->devicesClients)) {
			$this->createDeviceClient($device);

			return true;
		}

		$client = $this->devicesClients[$device->getPlainId()];

		if (!$client->isConnected()) {
			if (!$client->isConnecting()) {
				if (
					$client->getLastConnectAttempt() === null
					||
						($this->dateTimeFactory->getNow()->getTimestamp() - $client->getLastConnectAttempt()->getTimestamp())
						>= self::RECONNECT_COOL_DOWN_TIME
				) {
					unset($this->processedDevicesCommands[$device->getPlainId()]);

					$client->connect();

				} else {
					$this->consumer->append(new Entities\Messages\DeviceState(
						Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
						$this->connector->getId(),
						$device->getIdentifier(),
						false,
					));
				}
			}

			return true;
		}

		if (
			!$this->deviceConnectionManager->getState($device)->equalsValue(
				MetadataTypes\ConnectionState::STATE_CONNECTED,
			)
		) {
			return true;
		}

		if ($this->readDeviceData(self::CMD_STATUS, $device)) {
			return true;
		}

		return $this->writeChannelsProperty($device);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function readDeviceData(
		string $cmd,
		Entities\TuyaDevice $device,
	): bool
	{
		if (!array_key_exists($device->getPlainId(), $this->devicesClients)) {
			throw new Exceptions\InvalidState('Device client is not created');
		}

		$cmdResult = null;

		if (!array_key_exists($device->getPlainId(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getPlainId()] = [];
		}

		if (array_key_exists($cmd, $this->processedDevicesCommands[$device->getPlainId()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getPlainId()][$cmd];
		}

		if ($cmdResult === true) {
			return false;
		}

		if (
			$cmdResult instanceof DateTimeInterface
			&& ($this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp()) < self::SENDING_COMMAND_DELAY
		) {
			return true;
		}

		$this->processedDevicesCommands[$device->getPlainId()][$cmd] = $this->dateTimeFactory->getNow();

		if ($cmd === self::CMD_STATUS) {
			$this->devicesClients[$device->getPlainId()]->readStates()
				->then(function (array $statuses) use ($cmd, $device): void {
					$this->processedDevicesCommands[$device->getPlainId()][$cmd] = true;

					$dataPointsStatuses = [];

					foreach ($statuses as $status) {
						$dataPointsStatuses[] = new Entities\Messages\DataPointStatus(
							Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
							$status->getCode(),
							$status->getValue(),
						);
					}

					$this->consumer->append(new Entities\Messages\DeviceStatus(
						Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
						$this->connector->getId(),
						$device->getIdentifier(),
						$dataPointsStatuses,
					));
				})
				->otherwise(function (Throwable $ex) use ($device): void {
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
						],
					);

					$this->consumer->append(new Entities\Messages\DeviceState(
						Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
						$this->connector->getId(),
						$device->getIdentifier(),
						false,
					));
				});
		}

		return true;
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exception
	 */
	private function writeChannelsProperty(Entities\TuyaDevice $device): bool
	{
		if (!array_key_exists($device->getPlainId(), $this->devicesClients)) {
			throw new Exceptions\InvalidState('Device client is not created');
		}

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

				$expectedValue = DevicesUtilities\ValueHelper::flattenValue($state->getExpectedValue());

				if (
					$property->isSettable()
					&& $expectedValue !== null
					&& $state->isPending() === true
				) {
					$pending = is_string($state->getPending())
						? Utils\DateTime::createFromFormat(
							DateTimeInterface::ATOM,
							$state->getPending(),
						)
						: true;
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

					if (
						$pending === true
						|| (
							$pending !== false
							&& (float) $now->format('Uv') - (float) $pending->format('Uv') > 2_000
						)
					) {
						$this->processedProperties[$property->getPlainId()] = $now;

						$this->devicesClients[$device->getPlainId()]->writeState(
							$property->getIdentifier(),
							$expectedValue,
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
							->otherwise(function (Throwable $ex) use ($device, $property): void {
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
									],
								);

								$this->propertyStateHelper->setValue(
									$property,
									Utils\ArrayHash::from([
										DevicesStates\Property::EXPECTED_VALUE_KEY => null,
										DevicesStates\Property::PENDING_KEY => false,
									]),
								);

								$this->consumer->append(new Entities\Messages\DeviceState(
									Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
									$this->connector->getId(),
									$device->getIdentifier(),
									false,
								));

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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createDeviceClient(Entities\TuyaDevice $device): API\LocalApi
	{
		unset($this->processedDevicesCommands[$device->getPlainId()]);

		$client = $this->localApiFactory->create(
			$device->getIdentifier(),
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
		);

		$client->on(
			'message',
			function (Entities\API\Entity $message) use ($device): void {
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
						$device->getIdentifier(),
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

				$this->consumer->append(new Entities\Messages\DeviceState(
					Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
					$this->connector->getId(),
					$device->getIdentifier(),
					true,
				));
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

				$this->consumer->append(new Entities\Messages\DeviceState(
					Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
					$this->connector->getId(),
					$device->getIdentifier(),
					false,
				));
			},
		);

		$this->devicesClients[$device->getPlainId()] = $client;

		return $this->devicesClients[$device->getPlainId()];
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
