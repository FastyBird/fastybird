<?php declare(strict_types = 1);

/**
 * Local.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use DateTimeInterface;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\EventLoop;
use RuntimeException;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function is_bool;
use function is_numeric;
use function strval;

/**
 * Local devices client
 *
 * @package        FastyBird:ShellyConnector!
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

	private const CMD_STATE = 'state';

	/** @var array<string, API\Gen2WsApi> */
	private array $gen2DevicesWsClients = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Queue\Queue $queue,
		private readonly Helpers\Entity $entityHelper,
		private readonly Shelly\Logger $logger,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->handlerTimer = null;

		try {
			$gen1CoapClient = $this->connectionManager->getGen1CoapApiConnection();

			$gen1CoapClient->connect();

			$gen1CoapClient->on('message', function (Entities\API\Gen1\ReportDeviceState $message): void {
				$this->processGen1DeviceReportedStatus($message);
			});

			$gen1CoapClient->on('error', function (Throwable $ex): void {
				$this->logger->error(
					'An error occur in CoAP connection',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'local-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				if (!$ex instanceof Exceptions\CoapError) {
					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY),
							'CoAP client triggered an error',
						),
					);
				}
			});
		} catch (Throwable $ex) {
			$this->logger->error(
				'CoAP client could not be started',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'local-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->dispatcher?->dispatch(
				new DevicesEvents\TerminateConnector(
					MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY),
					'CoAP client could not be started',
				),
			);
		}

		$findDevicesQuery = new Queries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\ShellyDevice::class);

		foreach ($devices as $device) {
			if ($device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
				try {
					$client = $this->createGen2DeviceWsClient($device);

					$client->connect();
				} catch (Throwable $ex) {
					$this->logger->error(
						'Device websocket connection could not be created',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'local-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
						],
					);

					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY),
							'Websockets api client could not be started',
						),
					);
				}
			}
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
		$this->connectionManager->getGen1CoapApiConnection()->disconnect();

		foreach ($this->gen2DevicesWsClients as $client) {
			$client->disconnect();
		}

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
	 * @throws RuntimeException
	 */
	private function handleCommunication(): void
	{
		$findDevicesQuery = new Queries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\ShellyDevice::class) as $device) {
			$deviceState = $this->deviceConnectionManager->getState($device);

			if (
				!in_array($device->getId()->toString(), $this->processedDevices, true)
				&& !$deviceState->equalsValue(MetadataTypes\ConnectionState::STATE_ALERT)
			) {
				$this->processedDevices[] = $device->getId()->toString();

				if ($this->readDeviceStatus($device)) {
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
	 * @throws RuntimeException
	 */
	private function readDeviceStatus(Entities\ShellyDevice $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_STATE, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $device->getStatusReadingDelay()
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

		if ($device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
			$client = $this->getGen2DeviceWsClient($device);

			if ($client === null) {
				$client = $this->createGen2DeviceWsClient($device);
			}

			if (!$client->isConnected()) {
				if (!$client->isConnecting()) {
					if (
						$client->getLastConnectAttempt() === null
						|| (
							// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
							$this->dateTimeFactory->getNow()->getTimestamp() - $client->getLastConnectAttempt()->getTimestamp() >= self::RECONNECT_COOL_DOWN_TIME
						)
					) {
						$client->connect();

					} else {
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
					}
				}

				return false;
			}

			$client->readStates()
				->then(function (Entities\API\Gen2\GetDeviceState $response) use ($device): void {
					$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

					$this->processGen2DeviceGetState($device, $response);
				})
				->otherwise(function (Throwable $ex) use ($device): void {
					$this->logger->error(
						'Could not read device state',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'local-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'device' => [
								'id' => $device->getId()->toString(),
							],
						],
					);
				});

		} elseif ($device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_1)) {
			$address = $device->getLocalAddress();

			if ($address === null) {
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

				return true;
			}

			$this->connectionManager->getGen1HttpApiConnection()->getDeviceState(
				$address,
				$device->getUsername(),
				$device->getPassword(),
			)
				->then(function (Entities\API\Gen1\GetDeviceState $response) use ($device): void {
					$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector()->getId()->toString(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_CONNECTED,
							],
						),
					);

					$this->processGen1DeviceGetState($device, $response);
				})
				->otherwise(function (Throwable $ex) use ($device): void {
					if ($ex instanceof Exceptions\HttpApiError) {
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
					} elseif ($ex instanceof Exceptions\HttpApiCall) {
						if (
							$ex->getResponse() !== null
							&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_BAD_REQUEST
							&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS
						) {
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

						} elseif (
							$ex->getResponse() !== null
							&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
							&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED
						) {
							$this->queue->append(
								$this->entityHelper->create(
									Entities\Messages\StoreDeviceConnectionState::class,
									[
										'connector' => $device->getConnector()->getId()->toString(),
										'identifier' => $device->getIdentifier(),
										'state' => MetadataTypes\ConnectionState::STATE_LOST,
									],
								),
							);

						} else {
							$this->queue->append(
								$this->entityHelper->create(
									Entities\Messages\StoreDeviceConnectionState::class,
									[
										'connector' => $device->getConnector()->getId()->toString(),
										'identifier' => $device->getIdentifier(),
										'state' => MetadataTypes\ConnectionState::STATE_UNKNOWN,
									],
								),
							);
						}
					}

					$this->logger->error(
						'Could not read device state',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'local-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'device' => [
								'id' => $device->getId()->toString(),
							],
						],
					);
				});
		}

		return false;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createGen2DeviceWsClient(Entities\ShellyDevice $device): API\Gen2WsApi
	{
		if (array_key_exists($device->getId()->toString(), $this->gen2DevicesWsClients)) {
			throw new Exceptions\InvalidState('Gen 2 device WS client is already created');
		}

		unset($this->processedDevicesCommands[$device->getId()->toString()]);

		$client = $this->connectionManager->getGen2WsApiConnection($device);

		$client->on(
			'message',
			function (Entities\API\Gen2\GetDeviceState|Entities\API\Gen2\DeviceEvent $message) use ($device): void {
				if ($message instanceof Entities\API\Gen2\GetDeviceState) {
					$this->processGen2DeviceGetState($device, $message);
				}
			},
		);

		$client->on(
			'error',
			function (Throwable $ex) use ($device): void {
				$this->logger->warning(
					'Connection with Gen 2 device failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'local-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'device' => [
							'id' => $device->getId()->toString(),
						],
					],
				);

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
			},
		);

		$client->on(
			'connected',
			function () use ($client, $device): void {
				$this->logger->debug(
					'Connected to Gen 2 device',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'local-client',
						'device' => [
							'id' => $device->getId()->toString(),
						],
					],
				);

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $device->getConnector()->getId()->toString(),
							'identifier' => $device->getIdentifier(),
							'state' => MetadataTypes\ConnectionState::STATE_CONNECTED,
						],
					),
				);

				$client->readStates()
					->then(function (Entities\API\Gen2\GetDeviceState $state) use ($device): void {
						$this->processGen2DeviceGetState($device, $state);
					})
					->otherwise(function (Throwable $ex) use ($device): void {
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

						$this->logger->error(
							'An error occurred on initial Gen 2 device state reading',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
								'type' => 'local-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'device' => [
									'identifier' => $device->getIdentifier(),
								],
							],
						);
					});
			},
		);

		$client->on(
			'disconnected',
			function () use ($device): void {
				$this->logger->debug(
					'Disconnected from Gen 2 device',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'local-client',
						'device' => [
							'id' => $device->getId()->toString(),
						],
					],
				);

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
			},
		);

		$this->gen2DevicesWsClients[$device->getId()->toString()] = $client;

		return $this->gen2DevicesWsClients[$device->getId()->toString()];
	}

	private function getGen2DeviceWsClient(Entities\ShellyDevice $device): API\Gen2WsApi|null
	{
		return array_key_exists(
			$device->getId()->toString(),
			$this->gen2DevicesWsClients,
		)
			? $this->gen2DevicesWsClients[$device->getId()->toString()]
			: null;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processGen1DeviceGetState(
		Entities\ShellyDevice $device,
		Entities\API\Gen1\GetDeviceState $state,
	): void
	{
		$states = [];

		foreach ($state->getInputs() as $index => $input) {
			$findChannelsQuery = new DevicesQueries\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

			foreach ($channels as $channel) {
				if (Utils\Strings::endsWith($channel->getIdentifier(), '_' . $index)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::INPUT,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getInput(),
								),
							];
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::INPUT_EVENT,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getEvent(),
								),
							];
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::INPUT_EVENT_COUNT,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getEventCnt(),
								),
							];
						}
					}

					if (count($result) > 0) {
						$states[] = [
							'identifier' => $channel->getIdentifier(),
							'sensors' => $result,
						];
					}

					break;
				}
			}
		}

		foreach ($state->getMeters() as $index => $meter) {
			$findChannelsQuery = new DevicesQueries\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

			foreach ($channels as $channel) {
				if (Utils\Strings::endsWith($channel->getIdentifier(), '_' . $index)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (
							Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::ACTIVE_POWER,
							)
							|| Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::ROLLER_POWER,
							)
						) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$meter->getPower(),
								),
							];
						} elseif (
							(
								Utils\Strings::endsWith(
									$property->getIdentifier(),
									'_' . Types\SensorDescription::OVERPOWER,
								)
								|| Utils\Strings::endsWith(
									$property->getIdentifier(),
									'_' . Types\SensorDescription::OVERPOWER_VALUE,
								)
							)
						) {
							if (
								(
									$property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)
									&& is_bool($meter->getOverpower())
								) || (
									!$property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)
									&& is_numeric($meter->getOverpower())
								)
							) {
								$result[] = [
									'identifier' => $property->getIdentifier(),
									'value' => Helpers\Transformer::transformValueFromDevice(
										$property->getDataType(),
										$property->getFormat(),
										$meter->getOverpower(),
									),
								];
							}
						} elseif (
							Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::ENERGY,
							)
							|| Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::ROLLER_ENERGY,
							)
						) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$meter->getTotal(),
								),
							];
						}
					}

					if (count($result) > 0) {
						$states[] = [
							'identifier' => $channel->getIdentifier(),
							'sensors' => $result,
						];
					}

					break;
				}
			}
		}

		foreach ($state->getRelays() as $index => $relay) {
			$findChannelsQuery = new DevicesQueries\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

			foreach ($channels as $channel) {
				if (Utils\Strings::endsWith(
					$channel->getIdentifier(),
					Types\BlockDescription::RELAY . '_' . $index,
				)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::OUTPUT,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->getState(),
								),
							];
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::OVERPOWER,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->hasOverpower(),
								),
							];
						}
					}

					if (count($result) > 0) {
						$states[] = [
							'identifier' => $channel->getIdentifier(),
							'sensors' => $result,
						];
					}
				} elseif (Utils\Strings::endsWith($channel->getIdentifier(), Types\BlockDescription::DEVICE)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::OVERTEMPERATURE,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->hasOvertemperature(),
								),
							];
						}
					}

					if (count($result) > 0) {
						$states[] = [
							'identifier' => $channel->getIdentifier(),
							'sensors' => $result,
						];
					}
				}
			}
		}

		foreach ($state->getRollers() as $index => $roller) {
			$findChannelsQuery = new DevicesQueries\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

			foreach ($channels as $channel) {
				if (Utils\Strings::endsWith(
					$channel->getIdentifier(),
					Types\BlockDescription::ROLLER . '_' . $index,
				)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::ROLLER,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getState(),
								),
							];

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::ROLLER_POSITION,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getCurrentPosition(),
								),
							];

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::ROLLER_STOP_REASON,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getStopReason(),
								),
							];
						}
					}

					if (count($result) > 0) {
						$states[] = [
							'identifier' => $channel->getIdentifier(),
							'sensors' => $result,
						];
					}
				} elseif (Utils\Strings::endsWith($channel->getIdentifier(), Types\BlockDescription::DEVICE)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::OVERTEMPERATURE,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->hasOvertemperature(),
								),
							];
						}
					}

					if (count($result) > 0) {
						$states[] = [
							'identifier' => $channel->getIdentifier(),
							'sensors' => $result,
						];
					}
				}
			}
		}

		foreach ($state->getLights() as $index => $light) {
			$findChannelsQuery = new DevicesQueries\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

			foreach ($channels as $channel) {
				if (Utils\Strings::endsWith(
					$channel->getIdentifier(),
					Types\BlockDescription::LIGHT . '_' . $index,
				)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::RED,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getRed(),
								),
							];

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::GREEN,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getGreen(),
								),
							];
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::BLUE,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getBlue(),
								),
							];
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::GAIN,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getGain(),
								),
							];
						} elseif (
							Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::WHITE,
							)
							|| Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::WHITE_LEVEL,
							)
						) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getWhite(),
								),
							];
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::EFFECT,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getEffect(),
								),
							];
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::BRIGHTNESS,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getBrightness(),
								),
							];
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::OUTPUT,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getState(),
								),
							];
						}
					}

					if (count($result) > 0) {
						$states[] = [
							'identifier' => $channel->getIdentifier(),
							'sensors' => $result,
						];
					}

					break;
				}
			}
		}

		foreach ($state->getEmeters() as $index => $emeter) {
			$findChannelsQuery = new DevicesQueries\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

			foreach ($channels as $channel) {
				if (Utils\Strings::endsWith(
					$channel->getIdentifier(),
					Types\BlockDescription::EMETER . '_' . $index,
				)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::ACTIVE_POWER,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getActivePower(),
								),
							];

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::REACTIVE_POWER,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getReactivePower(),
								),
							];

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::POWER_FACTOR,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getPowerFactor(),
								),
							];

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::CURRENT,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getCurrent(),
								),
							];

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::VOLTAGE,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getVoltage(),
								),
							];

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::ENERGY,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getTotal(),
								),
							];

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::ENERGY_RETURNED,
						)) {
							$result[] = [
								'identifier' => $property->getIdentifier(),
								'value' => Helpers\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getTotalReturned(),
								),
							];
						}
					}

					if (count($result) > 0) {
						$states[] = [
							'identifier' => $channel->getIdentifier(),
							'sensors' => $result,
						];
					}

					break;
				}
			}
		}

		if (count($states) > 0) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceState::class,
					[
						'connector' => $device->getConnector()->getId()->toString(),
						'identifier' => $device->getIdentifier(),
						'ip_address' => $state->getWifi()?->getIp(),
						'state' => $states,
					],
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function processGen1DeviceReportedStatus(
		Entities\API\Gen1\ReportDeviceState $state,
	): void
	{
		$states = [];

		foreach ($state->getStates() as $blockState) {
			$property = $this->findGen1DeviceProperty(
				$state->getIdentifier(),
				$blockState->getSensor(),
			);

			if ($property !== null) {
				$states[] = [
					'identifier' => $property->getIdentifier(),
					'value' => Helpers\Transformer::transformValueFromDevice(
						$property->getDataType(),
						$property->getFormat(),
						$blockState->getValue(),
					),
				];
			}
		}

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\StoreDeviceConnectionState::class,
				[
					'connector' => $this->connector->getId()->toString(),
					'identifier' => $state->getIdentifier(),
					'state' => MetadataTypes\ConnectionState::STATE_CONNECTED,
				],
			),
		);

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\StoreDeviceState::class,
				[
					'connector' => $this->connector->getId()->toString(),
					'identifier' => $state->getIdentifier(),
					'ip_address' => $state->getIpAddress(),
					'state' => $states,
				],
			),
		);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function processGen2DeviceGetState(
		Entities\ShellyDevice $device,
		Entities\API\Gen2\GetDeviceState $state,
	): void
	{
		$states = array_map(
			function ($component) use ($device): array {
				$result = [];

				if ($component instanceof Entities\API\Gen2\DeviceSwitchState) {
					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ON
						),
					);

					if ($property !== null && $component->getOutput() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getOutput(),
							),
						];
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceCoverState) {
					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::STATE
						),
					);

					if ($property !== null && $component->getState() instanceof Types\CoverPayload) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								strval($component->getState()->getValue()),
							),
						];
					}

					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::POSITION
						),
					);

					if ($property !== null) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getCurrentPosition(),
							),
						];
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceLightState) {
					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ON
						),
					);

					if ($property !== null && $component->getOutput() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getOutput(),
							),
						];
					}

					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::BRIGHTNESS
						),
					);

					if ($property !== null && $component->getBrightness() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getBrightness(),
							),
						];
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceInputState) {
					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
						),
					);

					if ($property !== null) {
						if ($component->getState() instanceof Types\InputPayload) {
							$value = strval($component->getState()->getValue());
						} elseif ($component->getState() !== null) {
							$value = $component->getState();
						} else {
							$value = $component->getPercent();
						}

						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$value,
							),
						];
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceTemperatureState) {
					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::CELSIUS
						),
					);

					if (
						$property !== null
						&& $component->getTemperatureCelsius() !== Shelly\Constants::VALUE_NOT_AVAILABLE
					) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperatureCelsius(),
							),
						];
					}

					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::FAHRENHEIT
						),
					);

					if (
						$property !== null
						&& $component->getTemperatureFahrenheit() !== Shelly\Constants::VALUE_NOT_AVAILABLE
					) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperatureFahrenheit(),
							),
						];
					}
				} else {
					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
						),
					);

					if (
						$property !== null
						&& $component->getRelativeHumidity() !== Shelly\Constants::VALUE_NOT_AVAILABLE
					) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getRelativeHumidity(),
							),
						];
					}
				}

				if (
					$component instanceof Entities\API\Gen2\DeviceSwitchState
					|| $component instanceof Entities\API\Gen2\DeviceCoverState
				) {
					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ACTIVE_POWER
						),
					);

					if ($property !== null && $component->getActivePower() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getActivePower(),
							),
						];
					}

					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::POWER_FACTOR
						),
					);

					if ($property !== null && $component->getPowerFactor() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getPowerFactor(),
							),
						];
					}

					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ACTIVE_ENERGY
						),
					);

					if (
						$property !== null
						&& $component->getActiveEnergy() instanceof Entities\API\Gen2\ActiveEnergyStateBlock
					) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getActiveEnergy()->getTotal(),
							),
						];
					}

					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::CURRENT
						),
					);

					if ($property !== null && $component->getCurrent() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getCurrent(),
							),
						];
					}

					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::VOLTAGE
						),
					);

					if ($property !== null && $component->getVoltage() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getVoltage(),
							),
						];
					}

					$property = $this->findGen2DeviceProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::CELSIUS
						),
					);

					if (
						$property !== null
						&& $component->getTemperature() instanceof Entities\API\Gen2\TemperatureBlockState
					) {
						$result[] = [
							'identifier' => $property->getIdentifier(),
							'value' => Helpers\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperature()->getTemperatureCelsius(),
							),
						];
					}
				}

				return $result;
			},
			array_merge(
				$state->getSwitches(),
				$state->getCovers(),
				$state->getInputs(),
				$state->getLights(),
				$state->getTemperature(),
				$state->getHumidity(),
			),
		);

		$states = array_filter($states, static fn (array $item): bool => $item !== []);
		$states = array_merge([], ...$states);

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\StoreDeviceState::class,
				[
					'connector' => $device->getConnector()->getId()->toString(),
					'identifier' => $device->getIdentifier(),
					'ip_address' => $state->getEthernet()?->getIp() ?? $state->getWifi()?->getStaIp(),
					'state' => $states,
				],
			),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function findGen1DeviceProperty(
		string $deviceIdentifier,
		int $sensorIdentifier,
	): DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic|null
	{
		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->forConnector($this->connector);
		$findDeviceQuery->startWithIdentifier($deviceIdentifier);

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		if ($device === null) {
			return null;
		}

		$findChannelsQuery = new DevicesQueries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

		foreach ($channels as $channel) {
			foreach ($channel->getProperties() as $property) {
				if (
					$property instanceof DevicesEntities\Channels\Properties\Dynamic
					&& Utils\Strings::startsWith($property->getIdentifier(), strval($sensorIdentifier))
				) {
					return $property;
				}
			}
		}

		$findDevicePropertiesQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertiesQuery->forDevice($device);

		foreach ($this->devicePropertiesRepository->findAllBy($findDevicePropertiesQuery) as $property) {
			if (
				$property instanceof DevicesEntities\Devices\Properties\Dynamic
				&& Utils\Strings::startsWith($property->getIdentifier(), strval($sensorIdentifier))
			) {
				return $property;
			}
		}

		return null;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function findGen2DeviceProperty(
		Entities\ShellyDevice $device,
		string $propertyIdentifier,
	): DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic|null
	{
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier($propertyIdentifier);

		$property = $this->devicePropertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($property instanceof DevicesEntities\Devices\Properties\Dynamic) {
			return $property;
		}

		$findChannelsQuery = new DevicesQueries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

		foreach ($channels as $channel) {
			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier($propertyIdentifier);

			$property = $this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery);

			if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
				return $property;
			}
		}

		return null;
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
