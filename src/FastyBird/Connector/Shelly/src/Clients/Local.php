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
use FastyBird\Connector\Shelly\Consumers;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Types;
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
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function gethostbyname;
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
		private readonly Consumers\Messages $consumer,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
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

			$gen1CoapClient->on('error', static function (Throwable $ex): void {
			});
		} catch (Throwable $ex) {
			$this->logger->error(
				'CoAP client could not be started',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'local-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			throw new DevicesExceptions\Terminate(
				'CoAP client could not be started',
				$ex->getCode(),
				$ex,
			);
		}

		$findDevicesQuery = new Queries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\ShellyDevice::class);

		foreach ($devices as $device) {
			if ($device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
				try {
					$this->createGen2DeviceWsClient($device);

					$this->getGen2DeviceWsClient($device)?->connect();
				} catch (Throwable $ex) {
					$this->logger->error(
						'Websockets client could not be started',
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

					throw new DevicesExceptions\Terminate(
						'Websockets api client could not be started',
						$ex->getCode(),
						$ex,
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
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 * @throws Exceptions\WsError
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\ShellyDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic|MetadataEntities\DevicesModule\ChannelDynamicProperty $property,
	): Promise\PromiseInterface
	{
		$state = $this->channelPropertiesStates->getValue($property);

		if ($state === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property state could not be found. Nothing to write'),
			);
		}

		if (!$property->isSettable()) {
			return Promise\reject(new Exceptions\InvalidArgument('Provided property is not writable'));
		}

		if ($state->getExpectedValue() === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property expected value is not set. Nothing to write'),
			);
		}

		$valueToWrite = API\Transformer::transformValueToDevice(
			$property->getDataType(),
			$property->getFormat(),
			$state->getExpectedValue(),
		);

		if ($valueToWrite === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property expected value could not be transformed to device'),
			);
		}

		if ($state->isPending() === true) {
			$deferred = new Promise\Deferred();

			if ($device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
				$client = $this->getGen2DeviceWsClient($device);

				if ($client === null || !$client->isConnected()) {
					$address = $this->getDeviceAddress($device);

					if ($address === null) {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$device->getConnector()->getId(),
								$device->getIdentifier(),
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_ALERT),
							),
						);

						return Promise\reject(
							new Exceptions\InvalidState('Device is not properly configured. Address is missing'),
						);
					}

					$this->connectionManager->getGen2HttpApiConnection()->setDeviceStatus(
						$address,
						$device->getUsername(),
						$device->getPassword(),
						$property->getIdentifier(),
						$valueToWrite,
					)
						->then(static function () use ($deferred): void {
							$deferred->resolve();
						})
						->otherwise(function (Throwable $ex) use ($deferred, $device): void {
							if ($ex instanceof Exceptions\HttpApiError) {
								$this->consumer->append(
									new Entities\Messages\DeviceState(
										$this->connector->getId(),
										$device->getIdentifier(),
										MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_ALERT),
									),
								);
							} elseif ($ex instanceof Exceptions\HttpApiCall) {
								if (
									$ex->getResponse() !== null
									&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_BAD_REQUEST
									&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS
								) {
									$this->consumer->append(
										new Entities\Messages\DeviceState(
											$this->connector->getId(),
											$device->getIdentifier(),
											MetadataTypes\ConnectionState::get(
												MetadataTypes\ConnectionState::STATE_ALERT,
											),
										),
									);

								} elseif (
									$ex->getResponse() !== null
									&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
									&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED
								) {
									$this->consumer->append(
										new Entities\Messages\DeviceState(
											$this->connector->getId(),
											$device->getIdentifier(),
											MetadataTypes\ConnectionState::get(
												MetadataTypes\ConnectionState::STATE_LOST,
											),
										),
									);

								} else {
									$this->consumer->append(
										new Entities\Messages\DeviceState(
											$this->connector->getId(),
											$device->getIdentifier(),
											MetadataTypes\ConnectionState::get(
												MetadataTypes\ConnectionState::STATE_UNKNOWN,
											),
										),
									);
								}
							}

							$deferred->reject($ex);
						});
				} else {
					$client->writeState(
						$property->getIdentifier(),
						$valueToWrite,
					)
						->then(static function () use ($deferred): void {
							$deferred->resolve();
						})
						->otherwise(static function (Throwable $ex) use ($deferred): void {
							$deferred->reject($ex);
						});
				}
			} elseif ($device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_1)) {
				$address = $this->getDeviceAddress($device);

				if ($address === null) {
					$this->consumer->append(
						new Entities\Messages\DeviceState(
							$device->getConnector()->getId(),
							$device->getIdentifier(),
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_ALERT),
						),
					);

					return Promise\reject(
						new Exceptions\InvalidState('Device is not properly configured. Address is missing'),
					);
				}

				$this->connectionManager->getGen1HttpApiConnection()->setDeviceState(
					$address,
					$device->getUsername(),
					$device->getPassword(),
					$channel->getIdentifier(),
					$property->getIdentifier(),
					$valueToWrite,
				)
					->then(static function () use ($deferred): void {
						$deferred->resolve();
					})
					->otherwise(function (Throwable $ex) use ($deferred, $device): void {
						if ($ex instanceof Exceptions\HttpApiError) {
							$this->consumer->append(
								new Entities\Messages\DeviceState(
									$this->connector->getId(),
									$device->getIdentifier(),
									MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_ALERT),
								),
							);
						} elseif ($ex instanceof Exceptions\HttpApiCall) {
							if (
								$ex->getResponse() !== null
								&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_BAD_REQUEST
								&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS
							) {
								$this->consumer->append(
									new Entities\Messages\DeviceState(
										$this->connector->getId(),
										$device->getIdentifier(),
										MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_ALERT),
									),
								);

							} elseif (
								$ex->getResponse() !== null
								&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
								&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED
							) {
								$this->consumer->append(
									new Entities\Messages\DeviceState(
										$this->connector->getId(),
										$device->getIdentifier(),
										MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
									),
								);

							} else {
								$this->consumer->append(
									new Entities\Messages\DeviceState(
										$this->connector->getId(),
										$device->getIdentifier(),
										MetadataTypes\ConnectionState::get(
											MetadataTypes\ConnectionState::STATE_UNKNOWN,
										),
									),
								);
							}
						}

						$deferred->reject($ex);
					});
			} else {
				return Promise\reject(
					new Exceptions\InvalidState(
						'Device is not properly configured. Device generation definition is missing',
					),
				);
			}

			return $deferred->promise();
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
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
			if (
				!in_array($device->getPlainId(), $this->processedDevices, true)
				&& !$this->deviceConnectionManager->getState($device)->equalsValue(
					MetadataTypes\ConnectionState::STATE_ALERT,
				)
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
						$client
							->connect()
							->then(function () use ($device): void {
								$this->logger->debug(
									'Connected to Shelly Gen 2 device',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
										'type' => 'local-client',
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
									'Shelly Gen 2 device client could not be created',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
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

								$this->consumer->append(
									new Entities\Messages\DeviceState(
										$device->getConnector()->getId(),
										$device->getIdentifier(),
										MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
									),
								);
							});

					} else {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$device->getConnector()->getId(),
								$device->getIdentifier(),
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
							),
						);
					}
				}

				return false;
			}

			$client->readStates()
				->then(function () use ($device): void {
					$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();
				})
				->otherwise(function (Throwable $ex) use ($device): void {
					$this->logger->error(
						'Could not read device status',
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
			$address = $this->getDeviceAddress($device);

			if ($address === null) {
				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$device->getConnector()->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_ALERT),
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

					$this->consumer->append(
						new Entities\Messages\DeviceState(
							$this->connector->getId(),
							$device->getIdentifier(),
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_CONNECTED),
						),
					);

					$this->processGen1DeviceGetState($device, $response);
				})
				->otherwise(function (Throwable $ex) use ($device): void {
					if ($ex instanceof Exceptions\HttpApiError) {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$this->connector->getId(),
								$device->getIdentifier(),
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_ALERT),
							),
						);
					} elseif ($ex instanceof Exceptions\HttpApiCall) {
						if (
							$ex->getResponse() !== null
							&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_BAD_REQUEST
							&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS
						) {
							$this->consumer->append(
								new Entities\Messages\DeviceState(
									$this->connector->getId(),
									$device->getIdentifier(),
									MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_ALERT),
								),
							);

						} elseif (
							$ex->getResponse() !== null
							&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
							&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED
						) {
							$this->consumer->append(
								new Entities\Messages\DeviceState(
									$this->connector->getId(),
									$device->getIdentifier(),
									MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
								),
							);

						} else {
							$this->consumer->append(
								new Entities\Messages\DeviceState(
									$this->connector->getId(),
									$device->getIdentifier(),
									MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN),
								),
							);
						}
					}

					$this->logger->error(
						'Could not read device status',
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
			throw new Exceptions\InvalidState('Generation 2 device WS client is already created');
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

		$client->on('error', function (Throwable $ex) use ($device): void {
			$this->logger->warning(
				'Connection with device failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'ws-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'device' => [
						'id' => $device->getPlainId(),
					],
				],
			);

			$this->consumer->append(
				new Entities\Messages\DeviceState(
					$device->getConnector()->getId(),
					$device->getIdentifier(),
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
				),
			);
		});

		$client->on(
			'connected',
			function () use ($client, $device): void {
				$this->logger->debug(
					'Connected to device',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'ws-client',
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$device->getConnector()->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_CONNECTED),
					),
				);

				$client->readStates()
					->then(function (Entities\API\Gen2\GetDeviceState $state) use ($device): void {
						$this->processGen2DeviceGetState($device, $state);
					})
					->otherwise(function (Throwable $ex) use ($device): void {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$device->getConnector()->getId(),
								$device->getIdentifier(),
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
							),
						);

						$this->logger->error(
							'An error occurred on initial device state reading',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
								'type' => 'ws-api',
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
					'Disconnected from device',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'ws-client',
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$device->getConnector()->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function getDeviceAddress(Entities\ShellyDevice $device): string|null
	{
		$domain = $device->getDomain();

		if ($domain !== null) {
			return gethostbyname($domain);
		}

		$ipAddress = $device->getIpAddress();

		if ($ipAddress !== null) {
			return $ipAddress;
		}

		$this->logger->error(
			'Device ip address or domain is not configured',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
				'type' => 'http-client',
				'device' => [
					'id' => $device->getId()->toString(),
				],
			],
		);

		return null;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
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
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getInput(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::INPUT_EVENT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getEvent(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::INPUT_EVENT_COUNT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getEventCnt(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$states[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
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
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$meter->getPower(),
								),
							);
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
								$result[] = new Entities\Messages\PropertyStatus(
									$property->getIdentifier(),
									API\Transformer::transformValueFromDevice(
										$property->getDataType(),
										$property->getFormat(),
										$meter->getOverpower(),
									),
								);
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
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$meter->getTotal(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$states[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
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
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->getState(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::OVERPOWER,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->hasOverpower(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$states[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}
				} elseif (Utils\Strings::endsWith($channel->getIdentifier(), Types\BlockDescription::DEVICE)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::OVERTEMPERATURE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->hasOvertemperature(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$states[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
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
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getState(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::ROLLER_POSITION,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getCurrentPosition(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::ROLLER_STOP_REASON,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getStopReason(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$states[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}
				} elseif (Utils\Strings::endsWith($channel->getIdentifier(), Types\BlockDescription::DEVICE)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::OVERTEMPERATURE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->hasOvertemperature(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$states[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
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
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getRed(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::GREEN,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getGreen(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::BLUE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getBlue(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::GAIN,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getGain(),
								),
							);
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
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getWhite(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::EFFECT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getEffect(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::BRIGHTNESS,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getBrightness(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::OUTPUT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getState(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$states[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
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
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getActivePower(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::REACTIVE_POWER,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getReactivePower(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::POWER_FACTOR,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getPowerFactor(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::CURRENT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getCurrent(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::VOLTAGE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getVoltage(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::ENERGY,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getTotal(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::ENERGY_RETURNED,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getTotalReturned(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$states[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}

					break;
				}
			}
		}

		if (count($states) > 0) {
			$this->consumer->append(
				new Entities\Messages\DeviceStatus(
					$device->getConnector()->getId(),
					$device->getIdentifier(),
					$state->getWifi()?->getIp(),
					$states,
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\ParseMessage
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
				$states[] = new Entities\Messages\PropertyStatus(
					$property->getIdentifier(),
					API\Transformer::transformValueFromDevice(
						$property->getDataType(),
						$property->getFormat(),
						$blockState->getValue(),
					),
				);
			}
		}

		$this->consumer->append(
			new Entities\Messages\DeviceState(
				$this->connector->getId(),
				$state->getIdentifier(),
				MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_CONNECTED),
			),
		);

		$this->consumer->append(
			new Entities\Messages\DeviceStatus(
				$this->connector->getId(),
				$state->getIdentifier(),
				$state->getIpAddress(),
				$states,
			),
		);
	}

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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getOutput(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								strval($component->getState()->getValue()),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getCurrentPosition(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getOutput(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getBrightness(),
							),
						);
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

						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$value,
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperatureCelsius(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperatureFahrenheit(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getRelativeHumidity(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getActivePower(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getPowerFactor(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getActiveEnergy()->getTotal(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getCurrent(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getVoltage(),
							),
						);
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
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperature()->getTemperatureCelsius(),
							),
						);
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

		$this->consumer->append(
			new Entities\Messages\DeviceStatus(
				$device->getConnector()->getId(),
				$device->getIdentifier(),
				$state->getEthernet()?->getIp() ?? $state->getWifi()?->getStaIp(),
				$states,
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
