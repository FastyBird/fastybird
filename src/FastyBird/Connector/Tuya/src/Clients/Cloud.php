<?php declare(strict_types = 1);

/**
 * Cloud.php
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
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Consumers;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Connector\Tuya\Writers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Log;
use Ratchet;
use Ratchet\RFC6455;
use React\EventLoop;
use React\Promise;
use React\Socket;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function assert;
use function base64_decode;
use function in_array;
use function intval;
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

	private const HEARTBEAT_DELAY = 600;

	public const WS_MESSAGE_SCHEMA_FILENAME = 'openpulsar_message.json';

	public const WS_MESSAGE_PAYLOAD_SCHEMA_FILENAME = 'openpulsar_payload.json';

	public const WS_MESSAGE_PAYLOAD_DATA_SCHEMA_FILENAME = 'openpulsar_data.json';

	private const CMD_STATUS = 'status';

	private const CMD_HEARTBEAT = 'hearbeat';

	/** @var Array<string> */
	private array $processedDevices = [];

	/** @var Array<string, Array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $pingTimer = null;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	private Ratchet\Client\WebSocket|null $wsConnection = null;

	private API\OpenApi $openApiApi;

	private Log\LoggerInterface $logger;

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function __construct(
		private readonly Entities\TuyaConnector $connector,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Device $deviceHelper,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly Consumers\Messages $consumer,
		API\OpenApiFactory $openApiApiFactory,
		private readonly Writers\Writer $writer,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->pingTimer = null;
		$this->handlerTimer = null;

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
					$this->connector,
					Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
				),
				'password' => $this->generatePassword(),
			],
		)
			->then(function (Ratchet\Client\WebSocket $connection): void {
				$this->logger->debug(
					'Connected to Tuya sockets server',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'connector' => [
							'id' => $this->connector->getPlainId(),
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
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'cloud-client',
							'connection' => [
								'code' => $code,
								'reason' => $reason,
							],
							'connector' => [
								'id' => $this->connector->getPlainId(),
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
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'cloud-client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
						],
					);

					throw new DevicesExceptions\Terminate(
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
								$this->connector,
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
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);

				throw new DevicesExceptions\Terminate(
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

		$this->writer->connect($this->connector, $this);
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

		$this->writer->disconnect();
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function writeChannelProperty(
		Entities\TuyaDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$state = $this->channelPropertiesStates->getValue($property);

		if ($state === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property state could not be found. Nothing to write'),
			);
		}

		$expectedValue = DevicesUtilities\ValueHelper::flattenValue($state->getExpectedValue());

		if (!$property->isSettable()) {
			return Promise\reject(new Exceptions\InvalidArgument('Provided property is not writable'));
		}

		if ($expectedValue === null) {
			return Promise\reject(
				new Exceptions\InvalidArgument('Property expected value is not set. Nothing to write'),
			);
		}

		if ($state->isPending() === true) {
			$this->openApiApi->setDeviceStatus(
				$device->getIdentifier(),
				$property->getIdentifier(),
				$expectedValue,
			)
				->then(function () use ($property, $deferred): void {
					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::PENDING_KEY => $this->dateTimeFactory->getNow()->format(
								DateTimeInterface::ATOM,
							),
						]),
					);

					$deferred->resolve();
				})
				->otherwise(function (Throwable $ex) use ($property, $deferred): void {
					$this->propertyStateHelper->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::EXPECTED_VALUE_KEY => null,
							DevicesStates\Property::PENDING_KEY => false,
						]),
					);

					if (!$ex instanceof Exceptions\OpenApiCall) {
						$this->logger->error(
							'Calling Tuya cloud failed',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'cloud-client',
								'exception' => [
									'message' => $ex->getMessage(),
									'code' => $ex->getCode(),
								],
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
					}

					$deferred->reject($ex);
				});

		} else {
			return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
		}

		return $deferred->promise();
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	private function handleCommunication(): void
	{
		if (!$this->openApiApi->isConnected()) {
			$this->openApiApi->connect();
		}

		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\TuyaDevice);

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
	 * @throws DevicesExceptions\Terminate
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	private function processDevice(Entities\TuyaDevice $device): bool
	{
		if ($this->readDeviceData(self::CMD_HEARTBEAT, $device)) {
			return true;
		}

		return $this->readDeviceData(self::CMD_STATUS, $device);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	private function readDeviceData(string $cmd, Entities\TuyaDevice $device): bool
	{
		$cmdResult = null;

		if (!array_key_exists($device->getIdentifier(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getIdentifier()] = [];
		}

		if (array_key_exists($cmd, $this->processedDevicesCommands[$device->getIdentifier()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getIdentifier()][$cmd];
		}

		$delay = null;

		if ($cmd === self::CMD_STATUS) {
			$delay = intval($this->deviceHelper->getConfiguration(
				$device,
				Types\DevicePropertyIdentifier::get(Types\DevicePropertyIdentifier::IDENTIFIER_STATUS_READING_DELAY),
			));

		} elseif ($cmd === self::CMD_HEARTBEAT) {
			$delay = self::HEARTBEAT_DELAY;
		}

		if (
			$delay === null && $cmdResult === null
			|| (
				$cmdResult instanceof DateTimeInterface
				&& ($this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp()) < $delay
			)
		) {
			return false;
		}

		$this->processedDevicesCommands[$device->getIdentifier()][$cmd] = $this->dateTimeFactory->getNow();

		if ($cmd === self::CMD_HEARTBEAT) {
			$this->openApiApi->getDeviceInformation($device->getIdentifier())
				->then(function (Entities\API\DeviceInformation $deviceInformation) use ($cmd, $device): void {
					$this->processedDevicesCommands[$device->getIdentifier()][$cmd] = $this->dateTimeFactory->getNow();

					$this->consumer->append(new Entities\Messages\DeviceState(
						Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_OPENAPI),
						$this->connector->getId(),
						$device->getIdentifier(),
						$deviceInformation->isOnline(),
					));
				})
				->otherwise(function (Throwable $ex): void {
					$this->logger->error(
						'Could not call cloud openapi',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'cloud-client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
						],
					);

					throw new DevicesExceptions\Terminate(
						'Could not call cloud openapi',
						$ex->getCode(),
						$ex,
					);
				});

		} elseif ($cmd === self::CMD_STATUS) {
			$this->openApiApi->getDeviceStatus($device->getIdentifier())
				->then(function (array $statuses) use ($cmd, $device): void {
					$this->processedDevicesCommands[$device->getIdentifier()][$cmd] = $this->dateTimeFactory->getNow();

					$excludeDps = [$this->deviceHelper->getConfiguration(
						$device,
						Types\DevicePropertyIdentifier::get(
							Types\DevicePropertyIdentifier::IDENTIFIER_READ_STATE_EXCLUDE_DPS,
						),
					)];

					$dataPointsStatuses = [];

					foreach ($statuses as $status) {
						if (!in_array($status->getCode(), $excludeDps, true)) {
							$dataPointsStatuses[] = new Entities\Messages\DataPointStatus(
								Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_OPENAPI),
								$status->getCode(),
								$status->getValue(),
							);
						}
					}

					$this->consumer->append(new Entities\Messages\DeviceStatus(
						Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_OPENAPI),
						$this->connector->getId(),
						$device->getIdentifier(),
						$dataPointsStatuses,
					));
				})
				->otherwise(function (Throwable $ex): void {
					if (!$ex instanceof Exceptions\OpenApiCall) {
						$this->logger->error(
							'Calling Tuya cloud failed',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'cloud-client',
								'exception' => [
									'message' => $ex->getMessage(),
									'code' => $ex->getCode(),
								],
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
					}
				});
		}

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'cloud-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'data' => [
						'message' => $message,
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
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
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'data' => [
							'message' => $message,
						],
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);
			}
		}

		if (!$message->offsetExists('payload')) {
			$this->logger->error(
				'Received Tuya WS message is invalid',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			return;
		}

		$this->logger->debug(
			'Received message origin payload',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'cloud-client',
				'data' => [
					'payload' => $payload,
				],
				'connector' => [
					'id' => $this->connector->getPlainId(),
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'cloud-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'data' => [
						'payload' => $payload,
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			return;
		}

		if (!$payload->offsetExists('data')) {
			$this->logger->error(
				'Received Tuya WS message payload is invalid',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			return;
		}

		$accessSecret = $this->connectorHelper->getConfiguration(
			$this->connector,
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'cloud-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			return;
		}

		$this->logger->debug(
			'Received message decrypted',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'cloud-client',
				'data' => $decryptedData,
				'connector' => [
					'id' => $this->connector->getPlainId(),
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'cloud-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'data' => [
						'data' => $decryptedData,
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function buildWsTopicUrl(): string
	{
		$endpoint = $this->connectorHelper->getConfiguration(
			$this->connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENPULSAR_ENDPOINT),
		);
		assert(is_string($endpoint));

		$accessId = $this->connectorHelper->getConfiguration(
			$this->connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
		);
		assert(is_string($accessId));

		$topic = $this->connectorHelper->getConfiguration(
			$this->connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENPULSAR_TOPIC),
		);
		assert(is_string($topic));

		return $endpoint . 'ws/v2/consumer/persistent/'
			. $accessId . '/out/' . $topic . '/' . $accessId . '-sub?ackTimeoutMillis=3000&subscriptionType=Failover';
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function generatePassword(): string
	{
		$accessId = $this->connectorHelper->getConfiguration(
			$this->connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
		);
		assert(is_string($accessId));

		$accessSecret = $this->connectorHelper->getConfiguration(
			$this->connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_SECRET),
		);
		assert(is_string($accessSecret));

		$passString = $accessId . md5($accessSecret);

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
