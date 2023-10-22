<?php declare(strict_types = 1);

/**
 * Devices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Devices
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Connector\Virtual\Devices;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Drivers;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use React\EventLoop;
use Throwable;
use function array_key_exists;
use function in_array;

/**
 * Devices service
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Devices
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Devices
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const RECONNECT_COOL_DOWN_TIME = 300.0;

	/** @var array<string, Drivers\Driver> */
	private array $devicesDrivers = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface|false> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Entities\VirtualConnector $connector,
		private readonly Drivers\DriversManager $driversManager,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly Virtual\Logger $logger,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	public function start(): void
	{
		$this->processedDevices = [];

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);
	}

	public function stop(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		foreach ($this->devicesDrivers as $service) {
			$service->disconnect();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	private function handleDevices(): void
	{
		$findDevicesQuery = new Queries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\VirtualDevice::class) as $device) {
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
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processDevice(Entities\VirtualDevice $device): bool
	{
		$service = $this->driversManager->getDriver($device);

		if (!$service->isConnected()) {
			if (!$service->isConnecting()) {
				if (
					$service->getLastConnectAttempt() === null
					|| (
						// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
						$this->dateTimeFactory->getNow()->getTimestamp() - $service->getLastConnectAttempt()->getTimestamp() >= self::RECONNECT_COOL_DOWN_TIME
					)
				) {
					$service
						->connect()
						->then(function () use ($device): void {
							$this->logger->debug(
								'Connected to virtual device',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
									'type' => 'devices-driver',
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
								],
							);
						})
						->otherwise(function (Throwable $ex) use ($device): void {
							$this->logger->error(
								'Virtual device service could not be created',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
									'type' => 'devices-driver',
									'exception' => BootstrapHelpers\Logger::buildException($ex),
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
								],
							);

							$this->queue->append(
								$this->entityHelper->create(
									Entities\Messages\StoreDeviceConnectionState::class,
									[
										'connector' => $device->getConnector()->getId(),
										'device' => $device->getId(),
										'state' => MetadataTypes\ConnectionState::STATE_LOST,
									],
								),
							);
						});

				} else {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector()->getId(),
								'device' => $device->getId(),
								'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
							],
						),
					);
				}
			}

			return false;
		}

		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = false;
		}

		$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()];

		if (
			$cmdResult instanceof DateTimeInterface
			&& (
				$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $device->getStateReadingDelay()
			)
		) {
			return false;
		}

		$this->processedDevicesCommands[$device->getId()->toString()] = $this->dateTimeFactory->getNow();

		$service->process()
			->then(function () use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()] = $this->dateTimeFactory->getNow();

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $device->getConnector()->getId(),
							'device' => $device->getId(),
							'state' => MetadataTypes\ConnectionState::STATE_CONNECTED,
						],
					),
				);
			})
			->otherwise(function (Throwable $ex) use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()] = false;

				$this->logger->warning(
					'Could not call local api',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
						'type' => 'devices-driver',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
					],
				);

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $device->getConnector()->getId(),
							'device' => $device->getId(),
							'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
						],
					),
				);
			});

		return true;
	}

	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			function (): void {
				$this->handleDevices();
			},
		);
	}

}
