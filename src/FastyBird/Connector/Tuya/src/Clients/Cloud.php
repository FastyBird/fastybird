<?php declare(strict_types = 1);

/**
 * Cloud.php
 *
 * @license        More in license.md
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
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Consumers;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Types;
use FastyBird\DateTimeFactory;
use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\Metadata;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Metadata\Schemas as MetadataSchemas;
use FastyBird\Metadata\Types as MetadataTypes;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Log;
use Ratchet;
use Ratchet\RFC6455;
use React\EventLoop;
use React\Socket;
use Throwable;
use function array_key_exists;
use function base64_decode;
use function in_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function md5;
use function openssl_decrypt;
use function React\Async\async;
use function strval;
use const DIRECTORY_SEPARATOR;
use const OPENSSL_RAW_DATA;

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

	private const PING_INTERVAL = 30;

	private const HANDLER_START_DELAY = 2;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HEARTBEAT_TIMEOUT = 600;

	private const SENDING_COMMAND_DELAY = 120;

	public const WS_MESSAGE_SCHEMA_FILENAME = 'openpulsar_message.json';

	public const WS_MESSAGE_PAYLOAD_SCHEMA_FILENAME = 'openpulsar_payload.json';

	public const WS_MESSAGE_PAYLOAD_DATA_SCHEMA_FILENAME = 'openpulsar_data.json';

	private const CMD_INFO = 'info';

	private const CMD_STATUS = 'status';

	private const CMD_HEARTBEAT = 'hearbeat';

	/** @var Array<string> */
	private array $processedDevices = [];

	/** @var Array<string, Array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	/** @var Array<string, DateTimeInterface> */
	private array $processedProperties = [];

	private EventLoop\TimerInterface|null $pingTimer = null;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	/** @var Array<string, EventLoop\TimerInterface> */
	private array $heartbeatTimers = [];

	private Ratchet\Client\WebSocket|null $wsConnection = null;

	private API\OpenApi $openApiApi;

	private Log\LoggerInterface $logger;

	/**
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function __construct(
		private readonly MetadataEntities\DevicesModule\Connector $connector,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly Consumers\Messages $consumer,
		API\OpenApiFactory $openApiApiFactory,
		private readonly DevicesModuleModels\DataStorage\DevicesRepository $devicesRepository,
		private readonly DevicesModuleModels\DataStorage\ChannelsRepository $channelsRepository,
		private readonly DevicesModuleModels\DataStorage\ChannelPropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModuleModels\States\DeviceConnectionStateManager $deviceConnectionStateManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		$this->openApiApi = $openApiApiFactory->create($this->connector);
	}

	/**
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws InvalidArgumentException
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
		$this->processedDevicesCommands = [];

		$this->processedProperties = [];

		$this->pingTimer = null;
		$this->handlerTimer = null;
		$this->heartbeatTimers = [];

		$reactConnector = new Socket\Connector([
			'dns' => '8.8.8.8',
			'timeout' => 10,
			'tls' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
				'check_hostname' => false,
			],
		]);

		$connector = new Ratchet\Client\Connector($this->eventLoop, $reactConnector);

		$connector(
			$this->buildWsTopicUrl(),
			[],
			[
				'Connection' => 'Upgrade',
				'username' => $this->connectorHelper->getConfiguration(
					$this->connector->getId(),
					Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
				),
				'password' => $this->generatePassword(),
			],
		)
			->then(function (Ratchet\Client\WebSocket $connection): void {
				$this->logger->debug(
					'Connected to Tuya sockets server',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'cloud-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->wsConnection = $connection;

				$connection->on('message', function (RFC6455\Messaging\MessageInterface $message): void {
					$this->handleWsMessage($message->getPayload());
				});

				$connection->on('close', function ($code = null, $reason = null): void {
					$this->logger->debug(
						'Connection to Tuya WS server was closed',
						[
							'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
							'type' => 'cloud-client',
							'connection' => [
								'code' => $code,
								'reason' => $reason,
							],
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);

					if ($this->pingTimer !== null) {
						$this->eventLoop->cancelTimer($this->pingTimer);

						$this->pingTimer = null;
					}

					$this->wsConnection = null;
				});

				$connection->on('error', function (Throwable $ex): void {
					$this->logger->error(
						'An error occurred on WS server connection',
						[
							'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
							'type' => 'cloud-client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);

					throw new DevicesModuleExceptions\Terminate(
						'Connection to WS server was terminated',
						$ex->getCode(),
						$ex,
					);
				});

				$this->pingTimer = $this->eventLoop->addPeriodicTimer(
					self::PING_INTERVAL,
					async(function () use ($connection): void {
						$connection->send(new RFC6455\Messaging\Frame(
							strval($this->connectorHelper->getConfiguration(
								$this->connector->getId(),
								Types\ConnectorPropertyIdentifier::get(
									Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID,
								),
							)),
							true,
							RFC6455\Messaging\Frame::OP_PING,
						));
					}),
				);
			})
			->otherwise(function (Throwable $ex): void {
				$this->logger->error(
					'Connection to Tuya WS server failed',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'cloud-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				throw new DevicesModuleExceptions\Terminate(
					'Connection to Tuya WS server failed',
					$ex->getCode(),
					$ex,
				);
			});

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);
	}

	public function disconnect(): void
	{
		$this->wsConnection?->close();

		if ($this->pingTimer !== null) {
			$this->eventLoop->cancelTimer($this->pingTimer);

			$this->pingTimer = null;
		}

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		foreach ($this->heartbeatTimers as $heartbeatTimer) {
			$this->eventLoop->cancelTimer($heartbeatTimer);
		}

		$this->heartbeatTimers = [];
	}

	/**
	 * @throws DevicesModuleExceptions\Terminate
	 * @throws Throwable
	 */
	private function handleCommunication(): void
	{
		if (!$this->openApiApi->isConnected()) {
			$this->openApiApi->connect();
		}

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
	 * @throws DevicesModuleExceptions\Terminate
	 * @throws Throwable
	 */
	private function processDevice(MetadataEntities\DevicesModule\Device $deviceItem): bool
	{
		if ($this->readDeviceData(self::CMD_INFO, $deviceItem)) {
			return true;
		}

		if ($this->readDeviceData(self::CMD_STATUS, $deviceItem)) {
			return true;
		}

		if ($this->readDeviceData(self::CMD_HEARTBEAT, $deviceItem)) {
			return true;
		}

		if (
			$this->deviceConnectionStateManager->getState($deviceItem)->equalsValue(
				MetadataTypes\ConnectionState::STATE_CONNECTED,
			)
		) {
			return $this->writeChannelsProperty($deviceItem);
		}

		return true;
	}

	/**
	 * @throws Throwable
	 */
	private function readDeviceData(
		string $cmd,
		MetadataEntities\DevicesModule\Device $deviceItem,
	): bool
	{
		$httpCmdResult = null;

		if (!array_key_exists($deviceItem->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$deviceItem->getId()->toString()] = [];
		}

		if (array_key_exists($cmd, $this->processedDevicesCommands[$deviceItem->getId()->toString()])) {
			$httpCmdResult = $this->processedDevicesCommands[$deviceItem->getId()->toString()][$cmd];
		}

		if ($httpCmdResult === true) {
			return false;
		}

		if (
			$httpCmdResult instanceof DateTimeInterface
			&& ($this->dateTimeFactory->getNow()->getTimestamp() - $httpCmdResult->getTimestamp()) < self::SENDING_COMMAND_DELAY
		) {
			return true;
		}

		$this->processedDevicesCommands[$deviceItem->getId()->toString()][$cmd] = $this->dateTimeFactory->getNow();

		if ($cmd === self::CMD_INFO || $cmd === self::CMD_HEARTBEAT) {
			if (
				$cmd === self::CMD_INFO
				&& $this->deviceConnectionStateManager->getState($deviceItem)->equalsValue(
					MetadataTypes\ConnectionState::STATE_CONNECTED,
				)
			) {
				$this->processedDevicesCommands[$deviceItem->getId()->toString()][$cmd] = true;

				return false;
			}

			$this->openApiApi->getDeviceInformation($deviceItem->getIdentifier())
				->then(function (Entities\API\DeviceInformation $deviceInformation) use ($cmd, $deviceItem): void {
					if ($cmd === self::CMD_HEARTBEAT) {
						$this->processedDevicesCommands[$deviceItem->getId()->toString()][$cmd] = true;
					} else {
						if ($deviceInformation->isOnline()) {
							$this->processedDevicesCommands[$deviceItem->getId()->toString()][$cmd] = true;
						}
					}

					$this->consumer->append(new Entities\Messages\DeviceState(
						Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_OPENAPI),
						$this->connector->getId(),
						$deviceItem->getIdentifier(),
						$deviceInformation->isOnline(),
					));

					if ($cmd === self::CMD_HEARTBEAT) {
						$this->heartbeatTimers[$deviceItem->getId()->toString()] = $this->eventLoop->addTimer(
							self::HEARTBEAT_TIMEOUT,
							function () use ($cmd, $deviceItem): void {
								unset($this->processedDevicesCommands[$deviceItem->getId()->toString()][$cmd]);
							},
						);
					}
				})
				->otherwise(function (Throwable $ex): void {
					$this->logger->error(
						'Could not call cloud openapi',
						[
							'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
							'type' => 'cloud-client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);

					throw new DevicesModuleExceptions\Terminate(
						'Could not call cloud openapi',
						$ex->getCode(),
						$ex,
					);
				});

		} elseif ($cmd === self::CMD_STATUS) {
			$this->openApiApi->getDeviceStatus($deviceItem->getIdentifier())
				->then(function (array $statuses) use ($cmd, $deviceItem): void {
					$this->processedDevicesCommands[$deviceItem->getId()->toString()][$cmd] = true;

					$dataPointsStatuses = [];

					foreach ($statuses as $status) {
						$dataPointsStatuses[] = new Entities\Messages\DataPointStatus(
							Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_OPENAPI),
							$status->getCode(),
							$status->getValue(),
						);
					}

					$this->consumer->append(new Entities\Messages\DeviceStatus(
						Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_OPENAPI),
						$this->connector->getId(),
						$deviceItem->getIdentifier(),
						$dataPointsStatuses,
					));
				})
				->otherwise(function (Throwable $ex): void {
					if (!$ex instanceof Exceptions\OpenApiCall) {
						$this->logger->error(
							'Calling Tuya cloud failed',
							[
								'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
								'type' => 'cloud-client',
								'exception' => [
									'message' => $ex->getMessage(),
									'code' => $ex->getCode(),
								],
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);

						throw new DevicesModuleExceptions\Terminate(
							'Calling Tuya cloud failed',
							$ex->getCode(),
							$ex,
						);
					}
				});
		}

		return true;
	}

	/**
	 * @throws DevicesModuleExceptions\Terminate
	 * @throws Throwable
	 */
	private function writeChannelsProperty(MetadataEntities\DevicesModule\Device $deviceItem): bool
	{
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

						$this->openApiApi->setDeviceStatus(
							$deviceItem->getIdentifier(),
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
							->otherwise(function (Throwable $ex) use ($propertyItem): void {
								$this->propertyStateHelper->setValue(
									$propertyItem,
									Utils\ArrayHash::from([
										'expectedValue' => null,
										'pending' => false,
									]),
								);

								unset($this->processedProperties[$propertyItem->getId()->toString()]);

								if (!$ex instanceof Exceptions\OpenApiCall) {
									$this->logger->error(
										'Calling Tuya cloud failed',
										[
											'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
											'type' => 'cloud-client',
											'exception' => [
												'message' => $ex->getMessage(),
												'code' => $ex->getCode(),
											],
											'connector' => [
												'id' => $this->connector->getId()->toString(),
											],
										],
									);

									throw new DevicesModuleExceptions\Terminate(
										'Calling Tuya cloud failed',
										$ex->getCode(),
										$ex,
									);
								}
							});

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function handleWsMessage(string $message): void
	{
		try {
			$message = $this->schemaValidator->validate(
				$message,
				$this->getSchemaFilePath(self::WS_MESSAGE_SCHEMA_FILENAME),
			);

		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData | Exceptions\OpenPulsarHandle $ex) {
			$this->logger->error(
				'Could not decode received Tuya WS message',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'cloud-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'data' => [
						'message' => $message,
					],
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		if ($this->wsConnection !== null && $message->offsetExists('messageId')) {
			try {
				// Confirm received message
				// Received message have to confirmed to be removed from queue on Tuya server side
				$this->wsConnection->send(Utils\Json::encode(['messageId' => $message->offsetGet('messageId')]));

			} catch (Utils\JsonException $ex) {
				$this->logger->error(
					'Could not confirm received Tuya WS message',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'cloud-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'data' => [
							'message' => $message,
						],
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);
			}
		}

		if (!$message->offsetExists('payload')) {
			$this->logger->error(
				'Received Tuya WS message is invalid',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		$payload = base64_decode(strval($message->offsetGet('payload')), true);

		if ($payload === false) {
			$this->logger->error(
				'Received Tuya WS message payload could not be decoded',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		$this->logger->debug(
			'Received message origin payload',
			[
				'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
				'type' => 'cloud-client',
				'data' => [
					'payload' => $payload,
				],
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		try {
			$payload = $this->schemaValidator->validate(
				$payload,
				$this->getSchemaFilePath(self::WS_MESSAGE_PAYLOAD_SCHEMA_FILENAME),
			);

		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData | Exceptions\OpenPulsarHandle $ex) {
			$this->logger->error(
				'Could not decode received Tuya WS message payload',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'cloud-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'data' => [
						'payload' => $payload,
					],
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		if (!$payload->offsetExists('data')) {
			$this->logger->error(
				'Received Tuya WS message payload is invalid',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		$data = base64_decode(strval($payload->offsetGet('data')), true);

		if ($data === false) {
			$this->logger->error(
				'Received Tuya WS message payload data could not be decoded',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		$accessSecret = $this->connectorHelper->getConfiguration(
			$this->connector->getId(),
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_SECRET),
		);

		$decodingKey = Utils\Strings::substring(strval($accessSecret), 8, 16);

		$decryptedData = openssl_decrypt(
			$data,
			'AES-128-ECB',
			$decodingKey,
			OPENSSL_RAW_DATA,
		);

		if ($decryptedData === false) {
			$this->logger->error(
				'Received Tuya WS message payload data could not be decrypted',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		$this->logger->debug(
			'Received message decrypted',
			[
				'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
				'type' => 'cloud-client',
				'data' => $decryptedData,
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		try {
			$decryptedData = $this->schemaValidator->validate(
				$decryptedData,
				$this->getSchemaFilePath(self::WS_MESSAGE_PAYLOAD_DATA_SCHEMA_FILENAME),
			);

		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData | Exceptions\OpenPulsarHandle $ex) {
			$this->logger->error(
				'Could not decode received Tuya WS message payload data decrypted',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'cloud-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'data' => [
						'data' => $decryptedData,
					],
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		if (
			$decryptedData->offsetExists('devId')
			&& $decryptedData->offsetExists('status')
			&& $decryptedData->offsetGet('status') instanceof Utils\ArrayHash
		) {
			$dataPointsStatuses = [];

			foreach ($decryptedData->status as $status) {
				if (
					!$status instanceof Utils\ArrayHash
					|| !$status->offsetExists('code')
					|| !$status->offsetExists('value')
				) {
					continue;
				}

				if (is_string($status->value) || is_bool($status->value) || is_numeric($status->value)) {
					$dataPointsStatuses[] = new Entities\Messages\DataPointStatus(
						Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_OPENPULSAR),
						$status->code,
						$status->value,
					);
				}
			}

			$this->consumer->append(new Entities\Messages\DeviceStatus(
				Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_OPENPULSAR),
				$this->connector->getId(),
				$decryptedData->devId,
				$dataPointsStatuses,
			));

			return;
		}

		if (
			$decryptedData->offsetExists('bizCode')
			&& (
				$decryptedData->offsetGet('bizCode') === Types\OpenPulsarMessageType::BIZ_CODE_ONLINE
				|| $decryptedData->offsetGet('bizCode') === Types\OpenPulsarMessageType::BIZ_CODE_OFFLINE
			)
		) {
			$this->consumer->append(new Entities\Messages\DeviceState(
				Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_OPENPULSAR),
				$this->connector->getId(),
				$decryptedData->devId,
				$decryptedData->offsetGet('bizCode') === Types\OpenPulsarMessageType::BIZ_CODE_ONLINE,
			));
		}
	}

	/**
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function buildWsTopicUrl(): string
	{
		return $this->connectorHelper->getConfiguration(
			$this->connector->getId(),
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENPULSAR_ENDPOINT),
		) . 'ws/v2/consumer/persistent/' . $this->connectorHelper->getConfiguration(
			$this->connector->getId(),
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
		) . '/out/' . $this->connectorHelper->getConfiguration(
			$this->connector->getId(),
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENPULSAR_TOPIC),
		) . '/' . $this->connectorHelper->getConfiguration(
			$this->connector->getId(),
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
		) . '-sub?ackTimeoutMillis=3000&subscriptionType=Failover';
	}

	/**
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function generatePassword(): string
	{
		$passString = $this->connectorHelper->getConfiguration(
			$this->connector->getId(),
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
		) . md5(
			strval($this->connectorHelper->getConfiguration(
				$this->connector->getId(),
				Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_SECRET),
			)),
		);

		return Utils\Strings::substring(md5($passString), 8, 16);
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
	 * @throws Exceptions\OpenPulsarHandle
	 */
	private function getSchemaFilePath(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Tuya\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);

		} catch (Nette\IOException) {
			throw new Exceptions\OpenPulsarHandle('Validation schema for response could not be loaded');
		}

		return $schema;
	}

}
