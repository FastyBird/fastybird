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
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use Throwable;
use function array_key_exists;
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
		private readonly MetadataEntities\DevicesModule\Connector $connector,
		private readonly Helpers\Device $deviceHelper,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly Consumers\Messages $consumer,
		private readonly API\LocalApiFactory $localApiFactory,
		private readonly DevicesModels\DataStorage\DevicesRepository $devicesRepository,
		private readonly DevicesModels\DataStorage\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\DataStorage\ChannelPropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModels\States\DeviceConnectionStateManager $deviceConnectionStateManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedProperties = [];

		foreach ($this->devicesRepository->findAllByConnector($this->connector->getId()) as $deviceItem) {
			$this->createDeviceClient($deviceItem);
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
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
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

		foreach ($this->devicesRepository->findAllByConnector($this->connector->getId()) as $device) {
			if (
				!in_array($device->getId()->toString(), $this->processedDevices, true)
				&& !$this->deviceConnectionStateManager->getState($device)->equalsValue(
					MetadataTypes\ConnectionState::STATE_STOPPED,
				)
			) {
				$this->processedDevices[] = $device->getId()->toString();

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
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Exception
	 */
	private function processDevice(MetadataEntities\DevicesModule\Device $deviceItem): bool
	{
		if (!array_key_exists($deviceItem->getId()->toString(), $this->devicesClients)) {
			$this->createDeviceClient($deviceItem);

			return true;
		}

		$client = $this->devicesClients[$deviceItem->getId()->toString()];

		if (!$client->isConnected()) {
			if (!$client->isConnecting()) {
				if (
					$client->getLastConnectAttempt() === null
					||
						($this->dateTimeFactory->getNow()->getTimestamp() - $client->getLastConnectAttempt()->getTimestamp())
						>= self::RECONNECT_COOL_DOWN_TIME
				) {
					unset($this->processedDevicesCommands[$deviceItem->getId()->toString()]);

					$client->connect();

				} else {
					$this->consumer->append(new Entities\Messages\DeviceState(
						Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
						$this->connector->getId(),
						$deviceItem->getIdentifier(),
						false,
					));
				}
			}

			return true;
		}

		if (
			!$this->deviceConnectionStateManager->getState($deviceItem)->equalsValue(
				MetadataTypes\ConnectionState::STATE_CONNECTED,
			)
		) {
			return true;
		}

		if ($this->readDeviceData(self::CMD_STATUS, $deviceItem)) {
			return true;
		}

		return $this->writeChannelsProperty($deviceItem);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function readDeviceData(
		string $cmd,
		MetadataEntities\DevicesModule\Device $deviceItem,
	): bool
	{
		if (!array_key_exists($deviceItem->getId()->toString(), $this->devicesClients)) {
			throw new Exceptions\InvalidState('Device client is not created');
		}

		$cmdResult = null;

		if (!array_key_exists($deviceItem->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$deviceItem->getId()->toString()] = [];
		}

		if (array_key_exists($cmd, $this->processedDevicesCommands[$deviceItem->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$deviceItem->getId()->toString()][$cmd];
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

		$this->processedDevicesCommands[$deviceItem->getId()->toString()][$cmd] = $this->dateTimeFactory->getNow();

		if ($cmd === self::CMD_STATUS) {
			$this->devicesClients[$deviceItem->getId()->toString()]->readStates()
				->then(function (array $statuses) use ($cmd, $deviceItem): void {
					$this->processedDevicesCommands[$deviceItem->getId()->toString()][$cmd] = true;

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
						$deviceItem->getIdentifier(),
						$dataPointsStatuses,
					));
				})
				->otherwise(function (Throwable $ex) use ($deviceItem): void {
					$this->logger->warning(
						'Could not call local api',
						[
							'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
							'type' => 'local-client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);

					$this->consumer->append(new Entities\Messages\DeviceState(
						Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
						$this->connector->getId(),
						$deviceItem->getIdentifier(),
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
	private function writeChannelsProperty(MetadataEntities\DevicesModule\Device $deviceItem): bool
	{
		if (!array_key_exists($deviceItem->getId()->toString(), $this->devicesClients)) {
			throw new Exceptions\InvalidState('Device client is not created');
		}

		$now = $this->dateTimeFactory->getNow();

		foreach ($this->channelsRepository->findAllByDevice($deviceItem->getId()) as $channelItem) {
			foreach ($this->channelPropertiesRepository->findAllByChannel(
				$channelItem->getId(),
				MetadataEntities\DevicesModule\ChannelDynamicProperty::class,
			) as $propertyItem) {
				if (
					$propertyItem->isSettable()
					&& $propertyItem->getExpectedValue() !== null
					&& $propertyItem->isPending() === true
				) {
					$pending = is_string($propertyItem->getPending())
						? Utils\DateTime::createFromFormat(
							DateTimeInterface::ATOM,
							$propertyItem->getPending(),
						)
						: true;
					$debounce = array_key_exists(
						$propertyItem->getId()->toString(),
						$this->processedProperties,
					)
						? $this->processedProperties[$propertyItem->getId()->toString()]
						: false;

					if (
						$debounce !== false
						&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < 500
					) {
						continue;
					}

					unset($this->processedProperties[$propertyItem->getId()->toString()]);

					if (
						$pending === true
						|| (
							$pending !== false
							&& (float) $now->format('Uv') - (float) $pending->format('Uv') > 2_000
						)
					) {
						$this->processedProperties[$propertyItem->getId()->toString()] = $now;

						$this->devicesClients[$deviceItem->getId()->toString()]->writeState(
							$propertyItem->getIdentifier(),
							$propertyItem->getExpectedValue(),
						)
							->then(function () use ($propertyItem): void {
								$this->propertyStateHelper->setValue(
									$propertyItem,
									Utils\ArrayHash::from([
										'pending' => $this->dateTimeFactory->getNow()->format(DateTimeInterface::ATOM),
									]),
								);
							})
							->otherwise(function (Throwable $ex) use ($deviceItem, $propertyItem): void {
								$this->logger->error(
									'Could not call local device api',
									[
										'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
										'type' => 'local-client',
										'exception' => [
											'message' => $ex->getMessage(),
											'code' => $ex->getCode(),
										],
										'connector' => [
											'id' => $this->connector->getId()->toString(),
										],
									],
								);

								$this->propertyStateHelper->setValue(
									$propertyItem,
									Utils\ArrayHash::from([
										'expectedValue' => null,
										'pending' => false,
									]),
								);

								$this->consumer->append(new Entities\Messages\DeviceState(
									Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
									$this->connector->getId(),
									$deviceItem->getIdentifier(),
									false,
								));

								unset($this->processedProperties[$propertyItem->getId()->toString()]);
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
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function createDeviceClient(MetadataEntities\DevicesModule\Device $deviceItem): API\LocalApi
	{
		unset($this->processedDevicesCommands[$deviceItem->getId()->toString()]);

		$client = $this->localApiFactory->create(
			$deviceItem->getIdentifier(),
			null,
			strval($this->deviceHelper->getConfiguration(
				$deviceItem->getId(),
				Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_LOCAL_KEY),
			)),
			strval($this->deviceHelper->getConfiguration(
				$deviceItem->getId(),
				Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
			)),
			Types\DeviceProtocolVersion::get(strval($this->deviceHelper->getConfiguration(
				$deviceItem->getId(),
				Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION),
			))),
		);

		$client->on(
			'message',
			function (Entities\API\Entity $message) use ($deviceItem): void {
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
						$deviceItem->getIdentifier(),
						$dataPointsStatuses,
					));
				}
			},
		);

		$client->on(
			'connected',
			function () use ($deviceItem): void {
				$this->logger->debug(
					'Connected to device',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'local-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'device' => [
							'id' => $deviceItem->getId()->toString(),
						],
					],
				);

				$this->consumer->append(new Entities\Messages\DeviceState(
					Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
					$this->connector->getId(),
					$deviceItem->getIdentifier(),
					true,
				));
			},
		);

		$client->on(
			'error',
			function (Throwable $ex) use ($deviceItem): void {
				$this->logger->warning(
					'Connection with device failed',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'local-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'device' => [
							'id' => $deviceItem->getId()->toString(),
						],
					],
				);

				$this->consumer->append(new Entities\Messages\DeviceState(
					Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_API),
					$this->connector->getId(),
					$deviceItem->getIdentifier(),
					false,
				));
			},
		);

		$this->devicesClients[$deviceItem->getId()->toString()] = $client;

		return $this->devicesClients[$deviceItem->getId()->toString()];
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
