<?php declare(strict_types = 1);

/**
 * Cloud.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\EventLoop;
use Throwable;
use function array_key_exists;
use function array_map;
use function in_array;

/**
 * Cloud devices client
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Cloud implements Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HEARTBEAT_DELAY = 600;

	private const CMD_STATE = 'state';

	private const CMD_HEARTBEAT = 'hearbeat';

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly API\ConnectionManager $connectionManager,
		private readonly Entities\TuyaConnector $connector,
		private readonly Queue\Queue $queue,
		private readonly Helpers\Entity $entityHelper,
		private readonly Tuya\Logger $logger,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->handlerTimer = null;

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->connect()
			->then(function (): void {
				$this->logger->debug(
					'Connected to Tuya cloud API',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);
			})
			->otherwise(function (Throwable $ex): void {
				$this->logger->error(
					'Tuya cloud API client could not be created',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA),
						'Tuya cloud API client could not be created',
					),
				);
			});

		$this->connectionManager
			->getCloudWsConnection($this->connector)
			->on(
				'message',
				function (Entities\API\ReportDeviceState|Entities\API\ReportDeviceOnline $message): void {
					if ($message instanceof Entities\API\ReportDeviceOnline) {
						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'identifier' => $message->getIdentifier(),
									'state' => $message->isOnline()
										? MetadataTypes\ConnectionState::STATE_CONNECTED
										: MetadataTypes\ConnectionState::STATE_DISCONNECTED,
								],
							),
						);
					} else {
						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'identifier' => $message->getIdentifier(),
									'data_points' => array_map(
										static fn (Entities\API\DataPointState $dps): array => [
											'code' => $dps->getCode(),
											'value' => $dps->getValue(),
										],
										$message->getDataPoints(),
									),
								],
							),
						);
					}
				},
			)
			->on('error', function (Throwable $ex): void {
				$this->logger->error(
					'An error occurred in Tuya cloud WS client',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA),
						'An error occurred in Tuya cloud WS client',
					),
				);
			})
			->connect()
			->then(function (): void {
				$this->logger->debug(
					'Connected to Tuya cloud WS server',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);
			})
			->otherwise(function (Throwable $ex): void {
				$this->logger->error(
					'Tuya cloud WS client could not be created',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA),
						'Tuya cloud WS client could not be created',
					),
				);
			});
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		$this->connectionManager
			->getCloudWsConnection($this->connector)
			->disconnect();

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->disconnect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	private function handleCommunication(): void
	{
		if (!$this->connectionManager->getCloudApiConnection($this->connector)->isConnected()) {
			$this->connectionManager->getCloudApiConnection($this->connector)->connect(false);
		}

		$findDevicesQuery = new Queries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\TuyaDevice::class) as $device) {
			if (
				!in_array($device->getId()->toString(), $this->processedDevices, true)
				&& !$this->deviceConnectionManager->getState($device)->equalsValue(
					MetadataTypes\ConnectionState::STATE_ALERT,
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	private function processDevice(Entities\TuyaDevice $device): bool
	{
		if ($this->readDeviceInformation($device)) {
			return true;
		}

		return $this->readDeviceState($device);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function readDeviceInformation(Entities\TuyaDevice $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < self::HEARTBEAT_DELAY
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->getDeviceDetail($device->getIdentifier())
			->then(function (Entities\API\Device $deviceInformation) use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $device->getConnector()->getId()->toString(),
							'identifier' => $device->getIdentifier(),
							'state' => $deviceInformation->isOnline()
								? MetadataTypes\ConnectionState::STATE_CONNECTED
								: MetadataTypes\ConnectionState::STATE_DISCONNECTED,
						],
					),
				);
			})
			->otherwise(function (Throwable $ex) use ($device): void {
				$this->logger->error(
					'Could not call cloud openapi',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				if ($ex instanceof Exceptions\OpenApiError) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector()->getId()->toString(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_ALERT,
							],
						),
					);
				} elseif ($ex instanceof Exceptions\OpenApiCall) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector()->getId()->toString(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
							],
						),
					);
				} else {
					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA),
							'Unhandled error occur',
						),
					);
				}
			});

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function readDeviceState(Entities\TuyaDevice $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_STATE, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $device->getStateReadingDelay()
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->getDeviceState($device->getIdentifier())
			->then(function (Entities\API\GetDeviceState $state) use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreChannelPropertyState::class,
						[
							'connector' => $device->getConnector()->getId()->toString(),
							'identifier' => $device->getIdentifier(),
							'data_points' => array_map(
								static fn (Entities\API\DeviceDataPointState $dps): array => [
									'code' => $dps->getCode(),
									'value' => $dps->getValue(),
								],
								$state->getResult(),
							),
						],
					),
				);
			})
			->otherwise(function (Throwable $ex) use ($device): void {
				$this->logger->warning(
					'Calling Tuya cloud failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'error' => $ex->getMessage(),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				if ($ex instanceof Exceptions\OpenApiError) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector()->getId()->toString(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_ALERT,
							],
						),
					);
				} elseif ($ex instanceof Exceptions\OpenApiCall) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector()->getId()->toString(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
							],
						),
					);
				} else {
					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA),
							'Unhandled error occur',
						),
					);
				}
			});

		return true;
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
