<?php declare(strict_types = 1);

/**
 * Gateway.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           13.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients;

use DateTimeInterface;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Consumers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Writers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use Throwable;
use function array_key_exists;
use function assert;
use function in_array;

/**
 * Gateway client
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gateway implements Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HEARTBEAT_DELAY = 600;

	private const CMD_STATUS = 'status';

	private const CMD_HEARTBEAT = 'hearbeat';

	private API\LanApi $lanApiApi;

	private Log\LoggerInterface $logger;

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Entities\NsPanelConnector $connector,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Consumers\Messages $consumer,
		private readonly Writers\Writer $writer,
		API\LanApiFactory $lanApiApiFactory,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		$this->lanApiApi = $lanApiApiFactory->create(
			$this->connector->getIdentifier(),
		);
	}

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

		$this->writer->connect($this->connector, $this);
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		$this->writer->disconnect($this->connector, $this);
	}

	public function writeChannelProperty(
		Entities\NsPanelDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		// TODO: Implement it

		return Promise\resolve(true);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleCommunication(): void
	{
		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class) as $device) {
			assert($device instanceof Entities\Devices\Gateway);

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
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processDevice(Entities\Devices\Gateway $device): bool
	{
		if ($this->readDeviceInformation($device)) {
			return true;
		}

		return $this->readDeviceStatus($device);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function readDeviceInformation(Entities\Devices\Gateway $device): bool
	{
		if ($device->getIpAddress() === null) {
			$this->consumer->append(
				new Entities\Messages\DeviceState(
					$this->connector->getId(),
					$device->getIdentifier(),
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
				),
			);

			return true;
		}

		if (!array_key_exists($device->getIdentifier(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getIdentifier()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedDevicesCommands[$device->getIdentifier()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getIdentifier()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < self::HEARTBEAT_DELAY
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getIdentifier()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

		$this->lanApiApi->getGatewayInfo($device->getIpAddress())
			->then(function () use ($device): void {
				$this->processedDevicesCommands[$device->getIdentifier()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$this->connector->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_CONNECTED),
					),
				);
			})
			->otherwise(function (Throwable $ex) use ($device): void {
				$this->logger->error(
					'Could not call cloud openapi',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'gateway-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$this->connector->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
					),
				);
			});

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function readDeviceStatus(Entities\Devices\Gateway $device): bool
	{
		if (
			$device->getIpAddress() === null
			|| $device->getAccessToken() === null
		) {
			$this->consumer->append(
				new Entities\Messages\DeviceState(
					$this->connector->getId(),
					$device->getIdentifier(),
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
				),
			);

			return true;
		}

		if (!array_key_exists($device->getIdentifier(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getIdentifier()] = [];
		}

		if (array_key_exists(self::CMD_STATUS, $this->processedDevicesCommands[$device->getIdentifier()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getIdentifier()][self::CMD_STATUS];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $device->getStatusReadingDelay()
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getIdentifier()][self::CMD_STATUS] = $this->dateTimeFactory->getNow();

		$this->lanApiApi->getSubDevices($device->getIdentifier(), $device->getAccessToken())
			->then(function (Entities\API\Response\GetSubDevices $subDevices) use ($device): void {
				$this->processedDevicesCommands[$device->getIdentifier()][self::CMD_STATUS] = $this->dateTimeFactory->getNow();

				foreach ($subDevices->getData()->getDevicesList() as $subDevice) {
					// Ignore third-party devices
					if ($subDevice->getThirdSerialNumber() !== null) {
						continue;
					}

					$this->consumer->append(
						new Entities\Messages\DeviceState(
							$this->connector->getId(),
							$subDevice->getSerialNumber(),
							$subDevice->isOnline() ? MetadataTypes\ConnectionState::get(
								MetadataTypes\ConnectionState::STATE_CONNECTED,
							) : MetadataTypes\ConnectionState::get(
								MetadataTypes\ConnectionState::STATE_DISCONNECTED,
							),
						),
					);

					$capabilityStatuses = [];

					foreach ($subDevice->getStatuses() as $status) {
						$capabilityStatuses[] = new Entities\Messages\CapabilityStatus(
							$status->getType(),
							$status->getValue(),
							$status->getName(),
						);
					}

					$this->consumer->append(new Entities\Messages\DeviceStatus(
						$this->connector->getId(),
						$device->getIdentifier(),
						$capabilityStatuses,
					));
				}
			})
			->otherwise(function (Throwable $ex): void {
				if ($ex instanceof Exceptions\LanApiCall) {
					$this->logger->warning(
						'Calling Tuya cloud failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
							'type' => 'gateway-client',
							'error' => $ex->getMessage(),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
						],
					);

					return;
				}

				$this->logger->error(
					'Calling Tuya cloud failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'gateway-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);

				throw new DevicesExceptions\Terminate(
					'Calling Tuya cloud failed',
					$ex->getCode(),
					$ex,
				);
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
