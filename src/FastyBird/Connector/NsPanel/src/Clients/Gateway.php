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
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Orisai\ObjectMapper;
use React\EventLoop;
use React\Promise;
use Throwable;
use function array_key_exists;
use function in_array;
use function is_string;
use function preg_match;

/**
 * Connector gateway client
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
		private readonly Writers\Writer $writer,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly ObjectMapper\Processing\Processor $entityMapper,
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\NsPanelDevice $device,
		Entities\NsPanelChannel $channel,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped|MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty $property,
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
			return Promise\reject(new Exceptions\InvalidArgument('Device capability status could not be created'));
		}

		if ($state->isPending() === true) {
			try {
				return $this->lanApiApi->setSubDeviceStatus(
					$device->getIdentifier(),
					$status,
					$device->getGateway()->getIpAddress(),
					$device->getGateway()->getAccessToken(),
				);
			} catch (Throwable $ex) {
				return Promise\reject(new Exceptions\InvalidState('Request could not be handled', $ex->getCode(), $ex));
			}
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleCommunication(): void
	{
		$findDevicesQuery = new Queries\FindGatewayDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class) as $device) {
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
	 * @throws Exceptions\Runtime
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
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function readDeviceInformation(Entities\Devices\Gateway $gateway): bool
	{
		if (
			$gateway->getIpAddress() === null
			|| $gateway->getAccessToken() === null
		) {
			$this->consumer->append(
				$this->createEntity(
					Entities\Messages\DeviceState::class,
					[
						'connector' => $this->connector->getId()->toString(),
						'identifier' => $gateway->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::STATE_ALERT,
					],
				),
			);

			return true;
		}

		if (!array_key_exists($gateway->getIdentifier(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$gateway->getIdentifier()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedDevicesCommands[$gateway->getIdentifier()])) {
			$cmdResult = $this->processedDevicesCommands[$gateway->getIdentifier()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < self::HEARTBEAT_DELAY
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$gateway->getIdentifier()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

		try {
			$this->lanApiApi->getGatewayInfo($gateway->getIpAddress())
				->then(function () use ($gateway): void {
					$this->processedDevicesCommands[$gateway->getIdentifier()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

					$this->consumer->append(
						$this->createEntity(
							Entities\Messages\DeviceState::class,
							[
								'connector' => $this->connector->getId()->toString(),
								'identifier' => $gateway->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_CONNECTED,
							],
						),
					);
				})
				->otherwise(function (Throwable $ex) use ($gateway): void {
					if ($ex instanceof Exceptions\LanApiCall) {
						$this->logger->error(
							'Could not NS Panel API',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
								'type' => 'gateway-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
								'request' => [
									'body' => $ex->getRequest()?->getBody()->getContents(),
								],
								'response' => [
									'body' => $ex->getRequest()?->getBody()->getContents(),
								],
							],
						);

						$this->consumer->append(
							$this->createEntity(
								Entities\Messages\DeviceState::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'identifier' => $gateway->getIdentifier(),
									'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
								],
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
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $gateway->getId()->toString(),
							],
						],
					);

					$this->consumer->append(
						$this->createEntity(
							Entities\Messages\DeviceState::class,
							[
								'connector' => $this->connector->getId()->toString(),
								'identifier' => $gateway->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_LOST,
							],
						),
					);
				});
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occur',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'gateway-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $gateway->getId()->toString(),
					],
				],
			);

			return false;
		}

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function readDeviceStatus(Entities\Devices\Gateway $gateway): bool
	{
		if (
			$gateway->getIpAddress() === null
			|| $gateway->getAccessToken() === null
		) {
			$this->consumer->append(
				$this->createEntity(
					Entities\Messages\DeviceState::class,
					[
						'connector' => $this->connector->getId()->toString(),
						'identifier' => $gateway->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::STATE_ALERT,
					],
				),
			);

			return true;
		}

		if (!array_key_exists($gateway->getIdentifier(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$gateway->getIdentifier()] = [];
		}

		if (array_key_exists(self::CMD_STATUS, $this->processedDevicesCommands[$gateway->getIdentifier()])) {
			$cmdResult = $this->processedDevicesCommands[$gateway->getIdentifier()][self::CMD_STATUS];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $gateway->getStatusReadingDelay()
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$gateway->getIdentifier()][self::CMD_STATUS] = $this->dateTimeFactory->getNow();

		try {
			$this->lanApiApi->getSubDevices($gateway->getIpAddress(), $gateway->getAccessToken())
				->then(function (Entities\API\Response\GetSubDevices $subDevices) use ($gateway): void {
					$this->processedDevicesCommands[$gateway->getIdentifier()][self::CMD_STATUS] = $this->dateTimeFactory->getNow();

					$this->consumer->append(
						$this->createEntity(
							Entities\Messages\DeviceState::class,
							[
								'connector' => $this->connector->getId()->toString(),
								'identifier' => $gateway->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_CONNECTED,
							],
						),
					);

					foreach ($subDevices->getData()->getDevicesList() as $subDevice) {
						// Ignore third-party devices
						if ($subDevice->getThirdSerialNumber() !== null) {
							continue;
						}

						$this->consumer->append(
							$this->createEntity(
								Entities\Messages\DeviceState::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'identifier' => $subDevice->getSerialNumber(),
									'state' => $subDevice->isOnline()
										? MetadataTypes\ConnectionState::STATE_CONNECTED
										: MetadataTypes\ConnectionState::STATE_DISCONNECTED,
								],
							),
						);

						$capabilityStatuses = [];

						foreach ($subDevice->getStatuses() as $key => $status) {
							$stateIdentifier = null;

							if (
								is_string($key)
								&& preg_match(NsPanel\Constants::STATE_NAME_KEY, $key, $matches) === 1
								&& array_key_exists('identifier', $matches)
							) {
								$stateIdentifier = $matches['identifier'];
							}

							$findChannelQuery = new Queries\FindChannels();
							$findChannelQuery->byDeviceIdentifier($subDevice->getSerialNumber());
							$findChannelQuery->byIdentifier(
								Helpers\Name::convertCapabilityToChannel($status->getType(), $stateIdentifier),
							);

							$channel = $this->channelsRepository->findOneBy(
								$findChannelQuery,
								Entities\NsPanelChannel::class,
							);

							if ($channel !== null) {
								foreach ($status->getProtocols() as $protocol => $value) {
									$protocol = Types\Protocol::get($protocol);

									$findChannelPropertiesQuery = new DevicesQueries\FindChannelDynamicProperties();
									$findChannelPropertiesQuery->forChannel($channel);
									$findChannelPropertiesQuery->byIdentifier(
										Helpers\Name::convertProtocolToProperty($protocol),
									);

									$property = $this->channelsPropertiesRepository->findOneBy(
										$findChannelPropertiesQuery,
										DevicesEntities\Channels\Properties\Dynamic::class,
									);

									if ($property === null) {
										continue;
									}

									$capabilityStatuses[] = [
										'chanel' => $channel->getId()->toString(),
										'property' => $property->getId()->toString(),
										'value' => Helpers\Transformer::transformValueFromDevice(
											$property->getDataType(),
											$property->getFormat(),
											$value,
										),
									];
								}
							}
						}

						$this->consumer->append(
							$this->createEntity(
								Entities\Messages\DeviceStatus::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'identifier' => $subDevice->getSerialNumber(),
									'statuses' => $capabilityStatuses,
								],
							),
						);
					}
				})
				->otherwise(function (Throwable $ex) use ($gateway): void {
					if ($ex instanceof Exceptions\LanApiCall) {
						$this->logger->warning(
							'Calling NS Panel API failed',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
								'type' => 'gateway-client',
								'error' => $ex->getMessage(),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
								'device' => [
									'id' => $gateway->getId()->toString(),
								],
								'request' => [
									'body' => $ex->getRequest()?->getBody()->getContents(),
								],
								'response' => [
									'body' => $ex->getRequest()?->getBody()->getContents(),
								],
							],
						);

						$this->consumer->append(
							$this->createEntity(
								Entities\Messages\DeviceState::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'identifier' => $gateway->getIdentifier(),
									'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
								],
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
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $gateway->getId()->toString(),
							],
						],
					);

					$this->consumer->append(
						$this->createEntity(
							Entities\Messages\DeviceState::class,
							[
								'connector' => $this->connector->getId()->toString(),
								'identifier' => $gateway->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_LOST,
							],
						),
					);
				});
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occur',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'gateway-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $gateway->getId()->toString(),
					],
				],
			);

			return false;
		}

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

	/**
	 * @template T of Entities\Messages\Entity
	 *
	 * @param class-string<T> $entity
	 * @param array<mixed> $data
	 *
	 * @return T
	 *
	 * @throws Exceptions\Runtime
	 */
	private function createEntity(string $entity, array $data): Entities\Messages\Entity
	{
		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->entityMapper->process($data, $entity, $options);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\Runtime('Could not map data to entity: ' . $errorPrinter->printError($ex));
		}
	}

}
