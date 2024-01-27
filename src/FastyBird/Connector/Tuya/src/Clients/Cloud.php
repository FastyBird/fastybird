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
use FastyBird\Connector\Tuya\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\EventLoop;
use Throwable;
use function array_key_exists;
use function array_map;
use function in_array;
use function React\Async\async;

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

	private const CMD_STATE = 'state';

	private const CMD_HEARTBEAT = 'heartbeat';

	/** @var array<string, MetadataDocuments\DevicesModule\Device>  */
	private array $devices = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Queue\Queue $queue,
		private readonly Helpers\Entity $entityHelper,
		private readonly Helpers\Device $deviceHelper,
		private readonly Tuya\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
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

		$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);
		$findDevicesQuery->byType(Entities\TuyaDevice::TYPE);

		foreach ($this->devicesConfigurationRepository->findAllBy($findDevicesQuery) as $device) {
			$this->devices[$device->getId()->toString()] = $device;
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			async(function (): void {
				$this->registerLoopHandler();
			}),
		);

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->connect()
			->then(function (): void {
				$this->logger->debug(
					'Connected to Tuya cloud API',
					[
						'source' => MetadataTypes\ConnectorSource::TUYA,
						'type' => 'cloud-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);
			})
			->catch(function (Throwable $ex): void {
				$this->logger->error(
					'Tuya cloud API client could not be created',
					[
						'source' => MetadataTypes\ConnectorSource::TUYA,
						'type' => 'cloud-client',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::TUYA),
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
									'connector' => $this->connector->getId(),
									'identifier' => $message->getIdentifier(),
									'state' => $message->isOnline()
										? MetadataTypes\ConnectionState::CONNECTED
										: MetadataTypes\ConnectionState::DISCONNECTED,
								],
							),
						);
					} else {
						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $this->connector->getId(),
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
						'source' => MetadataTypes\ConnectorSource::TUYA,
						'type' => 'cloud-client',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::TUYA),
						'An error occurred in Tuya cloud WS client',
					),
				);
			})
			->connect()
			->then(function (): void {
				$this->logger->debug(
					'Connected to Tuya cloud WS server',
					[
						'source' => MetadataTypes\ConnectorSource::TUYA,
						'type' => 'cloud-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);
			})
			->catch(function (Throwable $ex): void {
				$this->logger->error(
					'Tuya cloud WS client could not be created',
					[
						'source' => MetadataTypes\ConnectorSource::TUYA,
						'type' => 'cloud-client',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::TUYA),
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
	 * @throws Exception
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function handleCommunication(): void
	{
		if (!$this->connectionManager->getCloudApiConnection($this->connector)->isConnected()) {
			$this->connectionManager->getCloudApiConnection($this->connector)->connect(false);
		}

		foreach ($this->devices as $device) {
			if (!in_array($device->getId()->toString(), $this->processedDevices, true)) {
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
	 * @throws Exception
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function processDevice(MetadataDocuments\DevicesModule\Device $device): bool
	{
		if ($this->readDeviceInformation($device)) {
			return true;
		}

		return $this->readDeviceState($device);
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function readDeviceInformation(MetadataDocuments\DevicesModule\Device $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp()
					< $this->deviceHelper->getHeartbeatDelay($device)
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

		$deviceState = $this->deviceConnectionManager->getState($device);

		if ($deviceState->equalsValue(MetadataTypes\ConnectionState::ALERT)) {
			unset($this->devices[$device->getId()->toString()]);

			return false;
		}

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->getDeviceDetail($device->getIdentifier())
			->then(function (Entities\API\GetDevice $detail) use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $device->getConnector(),
							'identifier' => $device->getIdentifier(),
							'state' => $detail->getResult()->isOnline()
								? MetadataTypes\ConnectionState::CONNECTED
								: MetadataTypes\ConnectionState::DISCONNECTED,
						],
					),
				);
			})
			->catch(function (Throwable $ex) use ($device): void {
				$this->logger->error(
					'Could not call cloud openapi',
					[
						'source' => MetadataTypes\ConnectorSource::TUYA,
						'type' => 'cloud-client',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
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
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::ALERT,
							],
						),
					);
				} elseif ($ex instanceof Exceptions\OpenApiCall) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::DISCONNECTED,
							],
						),
					);
				} else {
					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::TUYA),
							'Unhandled error occur',
						),
					);
				}
			});

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function readDeviceState(MetadataDocuments\DevicesModule\Device $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_STATE, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp()
					< $this->deviceHelper->getStateReadingDelay($device)
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

		$deviceState = $this->deviceConnectionManager->getState($device);

		if ($deviceState->equalsValue(MetadataTypes\ConnectionState::ALERT)) {
			unset($this->devices[$device->getId()->toString()]);

			return false;
		}

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->getDeviceState($device->getIdentifier())
			->then(function (Entities\API\GetDeviceState $state) use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreChannelPropertyState::class,
						[
							'connector' => $device->getConnector(),
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
			->catch(function (Throwable $ex) use ($device): void {
				$this->logger->warning(
					'Calling Tuya cloud failed',
					[
						'source' => MetadataTypes\ConnectorSource::TUYA,
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
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::ALERT,
							],
						),
					);
				} elseif ($ex instanceof Exceptions\OpenApiCall) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::DISCONNECTED,
							],
						),
					);
				} else {
					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::TUYA),
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
			async(function (): void {
				$this->handleCommunication();
			}),
		);
	}

}
