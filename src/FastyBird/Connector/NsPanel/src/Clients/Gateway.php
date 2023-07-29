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
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Consumers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Types;
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
use React\EventLoop;
use React\Promise;
use Throwable;
use function array_key_exists;
use function assert;
use function in_array;
use function is_array;

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

	use TPropertiesMapper;
	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HEARTBEAT_DELAY = 600;

	private const CMD_STATUS = 'status';

	private const CMD_HEARTBEAT = 'hearbeat';

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private API\LanApi $lanApiApi;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		protected readonly Helpers\Property $propertyStateHelper,
		protected readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly Entities\NsPanelConnector $connector,
		private readonly Consumers\Messages $consumer,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Writers\Writer $writer,
		private readonly NsPanel\Logger $logger,
		API\LanApiFactory $lanApiApiFactory,
	)
	{
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

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\NsPanelDevice $device,
		Entities\NsPanelChannel $channel,
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property,
	): Promise\PromiseInterface
	{
		if (!$device instanceof Entities\Devices\SubDevice) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Only sub-device could be updated'),
			);
		}

		if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Only dynamic properties could be updated'),
			);
		}

		if (!$property->isSettable()) {
			return Promise\reject(new Exceptions\InvalidArgument('Provided property is not writable'));
		}

		if ($device->getGateway()->getIpAddress() === null || $device->getGateway()->getAccessToken() === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Device assigned NS Panel is not configured'),
			);
		}

		$state = $this->channelPropertiesStates->getValue($property);

		if ($state === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property state could not be found. Nothing to write'),
			);
		}

		$expectedValue = DevicesUtilities\ValueHelper::flattenValue($state->getExpectedValue());

		if ($expectedValue === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property expected value is not set. Nothing to write'),
			);
		}

		$status = $this->mapChannelToStatus($channel);

		if ($status === null) {
			return Promise\reject(new Exceptions\LanApiCall('Device capability status could not be created'));
		}

		if ($state->isPending() === true) {
			return $this->lanApiApi->setSubDeviceStatus(
				$device->getIdentifier(),
				$status,
				$device->getGateway()->getIpAddress(),
				$device->getGateway()->getAccessToken(),
			);
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleCommunication(): void
	{
		$findDevicesQuery = new Queries\FindGatewayDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class) as $device) {
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
				if ($ex instanceof Exceptions\LanApiCall) {
					$this->logger->error(
						'Could not NS Panel API',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
							'type' => 'gateway-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'device' => [
								'id' => $device->getPlainId(),
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

					return;
				}

				$this->logger->error(
					'Calling NS Panel API failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'gateway-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$this->connector->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
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
						$findChannelQuery = new Queries\FindChannels();
						$findChannelQuery->byDeviceIdentifier($subDevice->getSerialNumber());
						$findChannelQuery->byIdentifier(
							Helpers\Name::convertCapabilityToChannel($status->getType(), $status->getName()),
						);

						$channel = $this->channelsRepository->findOneBy(
							$findChannelQuery,
							Entities\NsPanelChannel::class,
						);

						if ($channel !== null) {
							$findChannelPropertiesQuery = new DevicesQueries\FindChannelDynamicProperties();
							$findChannelPropertiesQuery->forChannel($channel);
							$findChannelPropertiesQuery->byIdentifier($status->getType()->getValue());

							$properties = $this->channelsPropertiesRepository->findAllBy(
								$findChannelPropertiesQuery,
								DevicesEntities\Channels\Properties\Dynamic::class,
							);

							foreach ($properties as $property) {
								if (
									Helpers\Name::convertPropertyToProtocol($property->getIdentifier())->equalsValue(
										Types\Protocol::COLOR_RED,
									)
									&& $status instanceof Entities\API\Statuses\ColorRgb
								) {
									$value = $status->getRed();
								} elseif (
									Helpers\Name::convertPropertyToProtocol($property->getIdentifier())->equalsValue(
										Types\Protocol::COLOR_GREEN,
									)
									&& $status instanceof Entities\API\Statuses\ColorRgb
								) {
									$value = $status->getGreen();
								} elseif (
									Helpers\Name::convertPropertyToProtocol($property->getIdentifier())->equalsValue(
										Types\Protocol::COLOR_BLUE,
									)
									&& $status instanceof Entities\API\Statuses\ColorRgb
								) {
									$value = $status->getBlue();
								} else {
									$value = $status->getValue();
									assert(!is_array($value));
								}

								$capabilityStatuses[] = new Entities\Messages\CapabilityStatus(
									$channel->getId(),
									$property->getId(),
									Helpers\Transformer::transformValueFromDevice(
										$property->getDataType(),
										$property->getFormat(),
										$value,
									),
								);
							}
						}
					}

					$this->consumer->append(new Entities\Messages\DeviceStatus(
						$this->connector->getId(),
						$device->getIdentifier(),
						$capabilityStatuses,
					));
				}
			})
			->otherwise(function (Throwable $ex) use ($device): void {
				if ($ex instanceof Exceptions\LanApiCall) {
					$this->logger->warning(
						'Calling NS Panel API failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
							'type' => 'gateway-client',
							'error' => $ex->getMessage(),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'device' => [
								'id' => $device->getPlainId(),
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

					return;
				}

				$this->logger->error(
					'Calling NS Panel API failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'gateway-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$this->connector->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
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
				$this->handleCommunication();
			},
		);
	}

}
