<?php declare(strict_types = 1);

/**
 * LocalApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          0.13.0
 *
 * @date           31.08.22
 */

namespace FastyBird\Connector\Tuya\API;

use Brick\Math;
use DateTimeInterface;
use Evenement;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use React\Socket;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_slice;
use function array_values;
use function base64_decode;
use function base64_encode;
use function count;
use function crc32;
use function current;
use function hash_hmac;
use function in_array;
use function intval;
use function is_array;
use function is_bool;
use function is_int;
use function is_numeric;
use function is_string;
use function mb_convert_encoding;
use function md5;
use function openssl_decrypt;
use function openssl_encrypt;
use function ord;
use function pack;
use function range;
use function React\Async\async;
use function React\Async\await;
use function sprintf;
use function str_replace;
use function strval;
use function unpack;
use const DIRECTORY_SEPARATOR;
use const OPENSSL_RAW_DATA;

/**
 * Local UDP device interface
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LocalApi implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	public const DP_STATUS_MESSAGE_SCHEMA_FILENAME = 'localapi_dp_query.json';

	public const DP_QUERY_MESSAGE_SCHEMA_FILENAME = 'localapi_dp_status.json';

	public const WIFI_QUERY_MESSAGE_SCHEMA_FILENAME = 'localapi_wifi_query.json';

	private const SOCKET_PORT = 6_668;

	private const HEARTBEAT_INTERVAL = 7.0;

	private const HEARTBEAT_SEQ_NO = -100;

	private const HEARTBEAT_TIMEOUT = 10.0;

	private const WAIT_FOR_REPLY_TIMEOUT = 5.0;

	private const MESSAGE_PREFIX = [0, 0, 85, 170];

	private const MESSAGE_SUFFIX = [0, 0, 170, 85];

	private const NO_PROTOCOL_HEADER_COMMANDS = [
		Types\LocalDeviceCommand::CMD_DP_QUERY,
		Types\LocalDeviceCommand::CMD_DP_QUERY_NEW,
		Types\LocalDeviceCommand::CMD_UPDATE_DPS,
		Types\LocalDeviceCommand::CMD_HEART_BEAT,
		Types\LocalDeviceCommand::CMD_SESS_KEY_NEG_START,
		Types\LocalDeviceCommand::CMD_SESS_KEY_NEG_RESP,
		Types\LocalDeviceCommand::CMD_SESS_KEY_NEG_FINISH,
	];

	private Types\LocalDeviceType $deviceType;

	private int $sequenceNr = 0;

	private bool $connecting = false;

	private bool $connected = false;

	private bool $waitingForReading = false;

	/** @var array<string|int, int|float|string|null> */
	private array $dpsToRequest = [];

	private DateTimeInterface|null $lastConnectAttempt = null;

	private DateTimeInterface|null $lastHeartbeat = null;

	private DateTimeInterface|null $disconnected = null;

	private DateTimeInterface|null $lost = null;

	/** @var array<int, Promise\Deferred> */
	private array $messagesListeners = [];

	/** @var array<int, EventLoop\TimerInterface> */
	private array $messagesListenersTimers = [];

	private EventLoop\TimerInterface|null $heartBeatTimer = null;

	private Socket\ConnectionInterface|null $connection = null;

	private Log\LoggerInterface $logger;

	/**
	 * @param array<Entities\Clients\LocalChild> $children
	 */
	public function __construct(
		private readonly string $identifier,
		private readonly string|null $gateway,
		private readonly string|null $nodeId,
		private readonly string $localKey,
		private readonly string $ipAddress,
		private readonly Types\DeviceProtocolVersion $protocolVersion,
		private readonly array $children,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->deviceType = $this->nodeId !== null
			? Types\LocalDeviceType::get(Types\LocalDeviceType::ZIGBEE)
			: Types\LocalDeviceType::get(Types\LocalDeviceType::DEFAULT);

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V32)) {
			// 3.2 behaves like 3.3 with device22
			$this->deviceType = Types\LocalDeviceType::get(Types\LocalDeviceType::DEVICE_22);

		} elseif ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V34)) {
			$this->deviceType = Types\LocalDeviceType::get(Types\LocalDeviceType::DEVICE_V34);

		} elseif ($this->deviceType->equalsValue(Types\LocalDeviceType::DEVICE_V34)) {
			$this->deviceType = Types\LocalDeviceType::get(Types\LocalDeviceType::DEFAULT);
		}

		if (count($this->children) > 0) {
			$this->deviceType = Types\LocalDeviceType::get(Types\LocalDeviceType::GATEWAY);
		}

		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function connect(): Promise\PromiseInterface
	{
		$this->messagesListeners = [];
		$this->messagesListenersTimers = [];

		$this->heartBeatTimer = null;
		$this->lastHeartbeat = null;

		$this->connecting = true;
		$this->connected = false;

		$this->lastConnectAttempt = $this->dateTimeFactory->getNow();

		$deferred = new Promise\Deferred();

		try {
			$connector = new Socket\Connector($this->eventLoop);

			$connector->connect($this->ipAddress . ':' . self::SOCKET_PORT)
				->then(function (Socket\ConnectionInterface $connection) use ($deferred): void {
					$this->connecting = false;
					$this->connected = true;

					$this->disconnected = null;
					$this->lost = null;

					$this->connection = $connection;

					$this->connection->on('data', function ($chunk): void {
						$message = $this->decodePayload($chunk);

						if ($message !== null) {
							if (
								$message->getError() !== null
								&& $message->getError()->equalsValue(Types\LocalDeviceError::ERR_DEVICE_TYPE)
								&& $message->getCommand()->equalsValue(Types\LocalDeviceCommand::CMD_DP_QUERY)
							) {
								$this->logger->debug(
									'Rebuilding payload for device22',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
										'type' => 'local-api',
										'device' => [
											'identifier' => $this->identifier,
										],
									],
								);

								$this->sendRequest($message->getCommand(), null, $message->getSequence());

								return;
							}

							if (array_key_exists($message->getSequence(), $this->messagesListeners)) {
								$this->messagesListeners[$message->getSequence()]->resolve(
									$message->getError() ?? $message->getData(),
								);

								$this->eventLoop->cancelTimer($this->messagesListenersTimers[$message->getSequence()]);

								unset($this->messagesListeners[$message->getSequence()]);
								unset($this->messagesListenersTimers[$message->getSequence()]);

								return;
							}

							if ($message->getCommand()->equalsValue(Types\LocalDeviceCommand::CMD_HEART_BEAT)) {
								$this->lastHeartbeat = $this->dateTimeFactory->getNow();

								$this->logger->debug(
									'Device has replied to heartbeat',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
										'type' => 'local-api',
										'device' => [
											'identifier' => $this->identifier,
										],
									],
								);
							}

							if ($message->getCommand()->equalsValue(Types\LocalDeviceCommand::CMD_STATUS)) {
								$this->logger->debug(
									'Device has reported its status',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
										'type' => 'local-api',
										'device' => [
											'identifier' => $this->identifier,
										],
										'message' => [
											'data' => $message->getData(),
										],
									],
								);
							}

							$this->emit('message', [$message]);
						}
					});

					$this->connection->on('error', function (Throwable $ex): void {
						$this->lost();

						$this->logger->error(
							'An error occurred on device connection',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'local-api',
								'exception' => [
									'message' => $ex->getMessage(),
									'code' => $ex->getCode(),
								],
								'device' => [
									'identifier' => $this->identifier,
								],
							],
						);
					});

					$this->connection->on('close', function (): void {
						$this->disconnect();

						$this->logger->debug(
							'Connection with device was closed',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'local-api',
								'device' => [
									'identifier' => $this->identifier,
								],
							],
						);
					});

					$this->heartBeatTimer = $this->eventLoop->addPeriodicTimer(
						self::HEARTBEAT_INTERVAL,
						async(function (): void {
							if (
								$this->lastHeartbeat !== null
								&&
								($this->dateTimeFactory->getNow()->getTimestamp() - $this->lastHeartbeat->getTimestamp())
								>= self::HEARTBEAT_TIMEOUT
							) {
								$this->lost();

							} else {
								$this->logger->debug(
									'Sending ping to device',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
										'type' => 'local-api',
										'device' => [
											'identifier' => $this->identifier,
										],
									],
								);

								$this->sendRequest(
									Types\LocalDeviceCommand::get(Types\LocalDeviceCommand::CMD_HEART_BEAT),
									null,
									self::HEARTBEAT_SEQ_NO,
								);
							}
						}),
					);

					$this->emit('connected');

					if (
						$this->deviceType->equalsValue(Types\LocalDeviceType::DEVICE_22)
						&& $this->dpsToRequest === []
					) {
						// Try to find device's dps for special device type
						// TODO: $this->detectAvailableDps();
					}

					$deferred->resolve();
				})
				->otherwise(function (Throwable $ex) use ($deferred): void {
					$this->connection = null;

					$this->connecting = false;
					$this->connected = false;

					$this->emit('error', [$ex]);

					$deferred->reject($ex);
				});
		} catch (Throwable $ex) {
			$this->connecting = false;
			$this->connected = false;

			$this->logger->error(
				'Could not create connector',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'device' => [
						'identifier' => $this->identifier,
					],
				],
			);

			$this->emit('error', [$ex]);

			$deferred->reject($ex);
		}

		return $deferred->promise();
	}

	public function disconnect(): void
	{
		$this->connection?->close();
		$this->connection = null;

		$this->connecting = false;
		$this->connected = false;

		$this->disconnected = $this->dateTimeFactory->getNow();

		if ($this->heartBeatTimer !== null) {
			$this->eventLoop->cancelTimer($this->heartBeatTimer);
		}

		foreach ($this->messagesListenersTimers as $timer) {
			$this->eventLoop->cancelTimer($timer);
		}

		foreach ($this->messagesListeners as $listener) {
			$listener->reject(new Exceptions\LocalApiCall('Closing connection to device'));
		}
	}

	public function isConnecting(): bool
	{
		return $this->connecting;
	}

	public function isConnected(): bool
	{
		return $this->connection !== null && !$this->connecting && $this->connected;
	}

	public function getLastHeartbeat(): DateTimeInterface|null
	{
		return $this->lastHeartbeat;
	}

	public function getLastConnectAttempt(): DateTimeInterface|null
	{
		return $this->lastConnectAttempt;
	}

	public function getDisconnected(): DateTimeInterface|null
	{
		return $this->disconnected;
	}

	public function getLost(): DateTimeInterface|null
	{
		return $this->lost;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function readStates(string|null $child = null): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->waitingForReading) {
			Promise\reject(new Exceptions\LocalApiBusy('Client is waiting for device reply'));
		}

		$localChild = null;

		if ($child !== null) {
			$localChild = current(
				array_filter($this->children, static fn ($item): bool => $item->getIdentifier() === $child),
			);

			if ($localChild === false) {
				throw new Exceptions\InvalidArgument('Provided child identifier is not registered under parent');
			}
		}

		$sequenceNr = $this->sendRequest(
			Types\LocalDeviceCommand::get(Types\LocalDeviceCommand::CMD_DP_QUERY),
			null,
			null,
			$localChild,
		);

		$this->messagesListeners[$sequenceNr] = $deferred;

		$this->waitingForReading = true;

		$this->messagesListenersTimers[$sequenceNr] = $this->eventLoop->addTimer(
			self::WAIT_FOR_REPLY_TIMEOUT,
			async(function () use ($deferred, $sequenceNr): void {
				$deferred->reject(new Exceptions\LocalApiTimeout('Sending command to device failed'));

				$this->eventLoop->cancelTimer($this->messagesListenersTimers[$sequenceNr]);

				unset($this->messagesListeners[$sequenceNr]);
				unset($this->messagesListenersTimers[$sequenceNr]);
			}),
		);

		$promise = $deferred->promise();

		$promise
			->then(function (): void {
				$this->waitingForReading = false;
			})
			->otherwise(function (): void {
				$this->waitingForReading = false;
			});

		return $promise;
	}

	/**
	 * @param array<string, int|float|string|bool> $states
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function writeStates(array $states, string|null $child = null): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$localChild = null;

		if ($child !== null) {
			$localChild = current(
				array_filter(
					$this->children,
					static fn ($item): bool => $item->getIdentifier() === $child,
				),
			);

			if ($localChild === false) {
				throw new Exceptions\InvalidArgument('Provided child identifier is not registered under parent');
			}
		}

		$sequenceNr = $this->sendRequest(
			Types\LocalDeviceCommand::get(Types\LocalDeviceCommand::CMD_CONTROL),
			$states,
			null,
			$localChild,
		);

		$this->messagesListeners[$sequenceNr] = $deferred;

		$this->messagesListenersTimers[$sequenceNr] = $this->eventLoop->addTimer(
			self::WAIT_FOR_REPLY_TIMEOUT,
			async(function () use ($deferred, $sequenceNr): void {
				$deferred->reject(new Exceptions\LocalApiTimeout('Sending command to device failed'));

				$this->eventLoop->cancelTimer($this->messagesListenersTimers[$sequenceNr]);

				unset($this->messagesListeners[$sequenceNr]);
				unset($this->messagesListenersTimers[$sequenceNr]);
			}),
		);

		return $deferred->promise();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function writeState(
		string $idx,
		int|float|string|bool $value,
		string|null $child = null,
	): Promise\PromiseInterface
	{
		return $this->writeStates([$idx => $value], $child);
	}

	/**
	 * Return which data points are supported by the device
	 *
	 * device22 devices need a sort of bruteforce querying in order to detect the list of available dps
	 * experience shows that the dps available are usually in the ranges [1-25] and [100-110]
	 * and need to split the bruteforcing in different steps due to request payload limitation (max. length = 255)
	 *
	 * @return array<string, int|float|string|null>
	 *
	 * @throws Throwable
	 */
	public function detectAvailableDps(): array
	{
		$dpsCache = [];
		$ranges = [[2, 11], [11, 21], [21, 31], [100, 111]];

		foreach ($ranges as $dpsRange) {
			$this->dpsToRequest = ['1' => null];
			$this->addDpsToRequest(range($dpsRange[0], $dpsRange[1]));

			try {
				/** @var array<Entities\API\DeviceDataPointStatus>|Types\LocalDeviceError $deviceStatuses */
				$deviceStatuses = await($this->readStates());
			} catch (Throwable $ex) {
				$this->logger->error(
					'Reading status from device failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'local-api',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'device' => [
							'identifier' => $this->identifier,
						],
					],
				);

				throw $ex;
			}

			if (is_array($deviceStatuses)) {
				foreach ($deviceStatuses as $deviceStatus) {
					$dpsCache[$deviceStatus->getCode()] = null;
				}
			}

			if ($this->deviceType->equalsValue(Types\LocalDeviceType::DEFAULT)) {
				$this->dpsToRequest = $dpsCache;

				return $dpsCache;
			}
		}

		$this->dpsToRequest = $dpsCache;

		$this->logger->debug(
			'Detected device DPS',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'local-api',
				'device' => [
					'identifier' => $this->identifier,
				],
				'dps' => $dpsCache,
			],
		);

		return $dpsCache;
	}

	/**
	 * @param int|array<int, int> $dpIndicies
	 */
	private function addDpsToRequest(int|array $dpIndicies): void
	{
		if (is_int($dpIndicies)) {
			$this->dpsToRequest[strval($dpIndicies)] = null;

		} else {
			foreach ($dpIndicies as $index) {
				$this->dpsToRequest[strval($index)] = null;
			}
		}
	}

	private function lost(): void
	{
		$this->emit('lost');

		$this->lost = $this->dateTimeFactory->getNow();

		$this->disconnect();
	}

	/**
	 * @param array<string, int|float|string|bool>|null $data
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function sendRequest(
		Types\LocalDeviceCommand $command,
		array|null $data = null,
		int|null $sequenceNr = null,
		Entities\Clients\LocalChild|null $child = null,
	): int
	{
		if ($sequenceNr === null) {
			$this->sequenceNr++;

			$payloadSequenceNr = $this->sequenceNr;

		} else {
			$payloadSequenceNr = $sequenceNr;
		}

		$payload = $this->buildPayload($payloadSequenceNr, $command, $child, $data);

		$this->logger->debug(
			'Sending message to device',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'local-api',
				'device' => [
					'identifier' => $this->identifier,
				],
				'message' => [
					'command' => $command->getValue(),
					'data' => $data,
					'sequence' => $sequenceNr,
				],
			],
		);

		$this->connection?->write(pack('C*', ...$payload));

		return $this->sequenceNr;
	}

	/**
	 * @param array<string, string|int|float|bool>|null $data
	 *
	 * @return array<int>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function buildPayload(
		int $sequenceNr,
		Types\LocalDeviceCommand $command,
		Entities\Clients\LocalChild|null $child = null,
		array|null $data = null,
	): array
	{
		$header = [];
		$hmacKey = null;

		$deviceType = $child?->getType() ?? $this->deviceType;
		$deviceId = $child?->getIdentifier() ?? $this->identifier;
		$gatewayId = $child !== null ? $this->identifier : $this->gateway;
		$nodeId = $child?->getNodeId() ?? $this->nodeId;

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V31)) {
			$message = $this->generateData($command, $deviceId, $gatewayId, $deviceType, $nodeId, $data);

			if ($message->getPayload() === null) {
				throw new Exceptions\InvalidState('Payload could not be prepared');
			}

			if ($message->getCommand()->equalsValue(Types\LocalDeviceCommand::CMD_CONTROL)) {
				$payload = openssl_encrypt(
					$message->getPayload(),
					'AES-128-ECB',
					mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
					OPENSSL_RAW_DATA,
				);

				if ($payload === false) {
					throw new Exceptions\InvalidState('Payload could not be encrypted');
				}

				$payload = base64_encode($payload);

				$preMd5String = array_merge(
					(array) unpack('C*', 'data='),
					(array) unpack('C*', $payload),
					(array) unpack('C*', '||lpv='),
					(array) unpack('C*', Types\DeviceProtocolVersion::VERSION_V31 . '||'),
					(array) unpack('C*', $this->localKey),
				);

				$hexDigest = md5(pack('C*', ...$preMd5String));
				$hexDigest = Nette\Utils\Strings::substring($hexDigest, 8);
				$hexDigest = Nette\Utils\Strings::substring($hexDigest, 0, 16);

				$header = array_merge(
					(array) unpack('C*', Types\DeviceProtocolVersion::VERSION_V31),
					(array) unpack('C*', $hexDigest),
				);

				$payload = array_merge($header, (array) unpack('C*', $payload));

			} else {
				$payload = unpack('C*', $message->getPayload());
			}

			if ($payload === false) {
				throw new Exceptions\InvalidState('Payload could not be build');
			}

			return $this->stitchPayload($sequenceNr, $payload, $command, $hmacKey);
		} elseif (
			$this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V32)
			|| $this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V33)
			|| $this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V34)
		) {
			$message = $this->generateData($command, $deviceId, $gatewayId, $deviceType, $nodeId, $data);

			if ($message->getPayload() === null) {
				throw new Exceptions\InvalidState('Payload could not be prepared');
			}

			if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V34)) {
				if (!in_array($message->getCommand()->getValue(), self::NO_PROTOCOL_HEADER_COMMANDS, true)) {
					$header = array_merge(
						(array) unpack('C*', strval($this->protocolVersion->getValue())),
						[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
					);
				}

				$payload = array_merge($header, (array) unpack('C*', $message->getPayload()));

				$payload = openssl_encrypt(
					pack('C*', ...$payload),
					'AES-128-ECB',
					mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
					OPENSSL_RAW_DATA,
				);

				if ($payload === false) {
					throw new Exceptions\InvalidState('Payload could not be encrypted');
				}

				$payload = (array) unpack('C*', $payload);

				$hmacKey = $this->localKey;

			} else {
				$payload = openssl_encrypt(
					$message->getPayload(),
					'AES-128-ECB',
					mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
					OPENSSL_RAW_DATA,
				);

				if ($payload === false) {
					throw new Exceptions\InvalidState('Payload could not be encrypted');
				}

				if (!in_array($message->getCommand()->getValue(), self::NO_PROTOCOL_HEADER_COMMANDS, true)) {
					$header = array_merge(
						(array) unpack('C*', strval($this->protocolVersion->getValue())),
						[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
					);
				}

				$payload = array_merge($header, (array) unpack('C*', $payload));
			}

			return $this->stitchPayload($sequenceNr, $payload, $message->getCommand(), $hmacKey);
		}

		throw new Exceptions\InvalidState(
			sprintf('Unknown protocol %s', strval($this->protocolVersion->getValue())),
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\LocalApiCall
	 */
	private function decodePayload(string $data): Entities\API\DeviceRawMessage|null
	{
		$headerLength = 16; // 4B prefix + 4B sequence nr + 4B command + 4B data length
		$footerLength = 8; // 4B CRC check + 4B suffix

		$useHmac = false;

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V34)) {
			$footerLength = 36; // 32B CRC check + 4B suffix

			$useHmac = true;
		}

		$buffer = unpack('C*', $data);

		if ($buffer === false) {
			return null;
		}

		$offset = $this->findPrefixIndexInMessage(self::MESSAGE_PREFIX, $buffer);

		$buffer = $offset > 1 ? array_values(array_slice($buffer, $offset)) : array_values($buffer);

		$bufferSize = count($buffer);

		try {
			$prefix = [$buffer[0], $buffer[1], $buffer[2], $buffer[3]];

			$sequenceNr = [$buffer[4], $buffer[5], $buffer[6], $buffer[7]];
			$sequenceNr = Math\BigInteger::fromBytes(pack('C*', ...$sequenceNr), false)->toInt();

			$command = [$buffer[8], $buffer[9], $buffer[10], $buffer[11]];
			$command = Math\BigInteger::fromBytes(pack('C*', ...$command), false)->toInt();

			$dataLength = [$buffer[12], $buffer[13], $buffer[14], $buffer[15]];
			$dataLength = Math\BigInteger::fromBytes(pack('C*', ...$dataLength), false)->toInt();
		} catch (Math\Exception\MathException $ex) {
			$this->logger->error(
				'Could not parse message parts - sequence nr, command & length',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'device' => [
						'identifier' => $this->identifier,
					],
				],
			);

			return null;
		}

		if ($prefix !== self::MESSAGE_PREFIX) {
			$this->logger->error(
				'Message prefix is not as expected',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'device' => [
						'identifier' => $this->identifier,
					],
					'message' => [
						'expected_prefix' => self::MESSAGE_PREFIX,
						'received_prefix' => $prefix,
					],
				],
			);

			return null;
		}

		if ($dataLength > 1_000) {
			$this->logger->error(
				'Header claims the packet size is over 1000 bytes!  It is most likely corrupt',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'device' => [
						'identifier' => $this->identifier,
					],
					'message' => [
						'length' => $dataLength,
					],
				],
			);

			return null;
		}

		if (!Types\LocalDeviceCommand::isValidValue($command)) {
			$this->logger->error(
				'Received unknown command',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'device' => [
						'identifier' => $this->identifier,
					],
					'message' => [
						'command' => $command,
						'sequence' => $sequenceNr,
					],
				],
			);

			return null;
		}

		$command = Types\LocalDeviceCommand::get($command);

		if (count($buffer) < $headerLength + $footerLength) {
			$this->logger->error(
				'Not enough data to unpack payload',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'device' => [
						'identifier' => $this->identifier,
					],
					'message' => [
						'command' => $command,
						'sequence' => $sequenceNr,
					],
				],
			);

			return null;
		}

		try {
			$returnCode = [$buffer[16], $buffer[17], $buffer[18], $buffer[19]];
			$returnCode = Math\BigInteger::fromBytes(pack('C*', ...$returnCode), false)->toInt();

			$footer = array_slice($buffer, -$footerLength);
			$crc = array_slice($footer, 0, count($footer) - 4);

			$crc = $useHmac ? pack('C*', ...$crc) : Math\BigInteger::fromBytes(pack('C*', ...$crc), false)->toInt();

			$suffix = array_slice($buffer, -4);

		} catch (Math\Exception\MathException $ex) {
			$this->logger->error(
				'Could not parse message parts - return code & crc',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'device' => [
						'identifier' => $this->identifier,
					],
				],
			);

			return null;
		}

		$hasReturnCode = ($returnCode & 0xFFFFFF00) === 0;

		$headerWithDataPart = array_slice($buffer, 0, $bufferSize - $footerLength);

		$calculatedCrc = $useHmac
			? hash_hmac('sha256', pack('C*', ...$headerWithDataPart), $this->localKey, true)
			: crc32(pack('C*', ...$headerWithDataPart));

		if ($calculatedCrc !== $crc) {
			$this->logger->error(
				$useHmac ? 'HMAC checksum is wrong' : 'CRC checksum is wrong',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'device' => [
						'identifier' => $this->identifier,
					],
					'message' => [
						'expected_crc' => $crc,
						'calculated_crc' => $calculatedCrc,
					],
				],
			);

			return null;
		}

		if ($suffix !== self::MESSAGE_SUFFIX) {
			$this->logger->error(
				'Message suffix is not as expected',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'device' => [
						'identifier' => $this->identifier,
					],
					'message' => [
						'expected_suffix' => self::MESSAGE_SUFFIX,
						'received_suffix' => $suffix,
					],
				],
			);

			return null;
		}

		$dataPart = array_values(array_slice($buffer, 20, $dataLength + $footerLength - 20));

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V34)) {
			$dataPart = openssl_decrypt(
				pack('C*', ...$dataPart),
				'AES-128-ECB',
				mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
				OPENSSL_RAW_DATA,
			);

			if ($dataPart === false) {
				$this->logger->error(
					'Received message data could not be decoded',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'local-api',
						'device' => [
							'identifier' => $this->identifier,
						],
					],
				);

				return null;
			}

			$dataPart = (array) unpack('C*', $dataPart);
		}

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V31)) {
			$payload = null;

			if (
				count($dataPart) > 2
				&& in_array(ord('{'), [$dataPart[0], $dataPart[1]], true)
			) {
				$payload = pack('C*', ...$dataPart);
			} elseif (
				count($dataPart) > 3
				&& unpack('C*', Types\DeviceProtocolVersion::VERSION_V31) === [$dataPart[0], $dataPart[1], $dataPart[2]]
			) {
				$this->logger->info(
					'Received message from device in version 3.1. This code is untested',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'local-api',
						'device' => [
							'identifier' => $this->identifier,
						],
					],
				);

				$dataPart = array_slice($dataPart, 3); // Remove version header

				// Remove (what I'm guessing, but not confirmed is) 16-bytes of MD5 hex digest of payload
				$dataPart = array_slice($dataPart, 16);

				$payload = openssl_decrypt(
					strval(base64_decode(pack('C*', ...$dataPart), true)),
					'AES-128-ECB',
					mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
					OPENSSL_RAW_DATA,
				);

				if ($payload === false) {
					$this->logger->error(
						'Received message payload could not be decoded',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'local-api',
							'device' => [
								'identifier' => $this->identifier,
							],
						],
					);

					return null;
				}
			}
		} elseif (
			$this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V32)
			|| $this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V33)
			|| $this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V34)
		) {
			$payload = null;

			if (count($dataPart) > 12) {
				if (
					array_values((array) unpack(
						'C*',
						Types\DeviceProtocolVersion::VERSION_V32,
					)) === [$dataPart[0], $dataPart[1], $dataPart[2]]
					|| array_values((array) unpack(
						'C*',
						Types\DeviceProtocolVersion::VERSION_V33,
					)) === [$dataPart[0], $dataPart[1], $dataPart[2]]
					|| array_values((array) unpack(
						'C*',
						Types\DeviceProtocolVersion::VERSION_V34,
					)) === [$dataPart[0], $dataPart[1], $dataPart[2]]
				) {
					$dataPart = array_slice($dataPart, 3); // Remove version header
				} elseif ($this->deviceType->equalsValue(Types\LocalDeviceType::DEVICE_22)) {
					$dataPart = array_slice($dataPart, 3); // Remove version header
				}

				if (!$this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V34)) {
					if ($command->equalsValue(Types\LocalDeviceCommand::CMD_STATUS)) {
						$dataPart = array_slice($dataPart, 12);
					}

					$payload = openssl_decrypt(
						pack('C*', ...$dataPart),
						'AES-128-ECB',
						mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
						OPENSSL_RAW_DATA,
					);

					if ($payload === false) {
						$this->logger->error(
							'Received message payload could not be decoded',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'local-api',
								'device' => [
									'identifier' => $this->identifier,
								],
							],
						);

						return null;
					}
				}
			}

			if (
				is_string($payload)
				&& Utils\Strings::contains(Utils\Strings::lower($payload), 'data unvalid')
				&& !$this->deviceType->equalsValue(Types\LocalDeviceType::DEVICE_22)
			) {
				if ($this->deviceType->equalsValue(Types\LocalDeviceType::GATEWAY)) {
					$payload = null;
				} elseif ($this->deviceType->equalsValue(Types\LocalDeviceType::DEFAULT)) {
					$this->deviceType = Types\LocalDeviceType::get(Types\LocalDeviceType::DEVICE_22);
					$this->dpsToRequest = ['1' => null];

					$this->logger->info(
						sprintf(
							'"data unvalid" error detected: switching to "%s" device type',
							strval($this->deviceType->getValue()),
						),
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'local-api',
							'device' => [
								'identifier' => $this->identifier,
							],
						],
					);

					return new Entities\API\DeviceRawMessage(
						$this->identifier,
						$command,
						$sequenceNr,
						$hasReturnCode ? $returnCode : null,
						null,
						Types\LocalDeviceError::get(Types\LocalDeviceError::ERR_DEVICE_TYPE),
					);
				}
			}

			if (
				is_string($payload)
				&& (
					Utils\Strings::contains(Utils\Strings::lower($payload), 'data unvalid')
					|| Utils\Strings::contains(Utils\Strings::lower($payload), 'format error')
				)
			) {
				$this->logger->error(
					'Data received from device are invalid',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'local-api',
						'device' => [
							'identifier' => $this->identifier,
							'payload' => $payload,
						],
					],
				);

				return new Entities\API\DeviceRawMessage(
					$this->identifier,
					$command,
					$sequenceNr,
					$hasReturnCode ? $returnCode : null,
					null,
					Types\LocalDeviceError::get(Types\LocalDeviceError::ERR_PAYLOAD),
				);
			}
		} else {
			$this->logger->warning(
				'Received message from device with unsupported version',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'device' => [
						'identifier' => $this->identifier,
					],
					'message' => [
						'command' => $command->getValue(),
						'sequence' => $sequenceNr,
						'returnCode' => $returnCode,
					],
				],
			);

			return null;
		}

		$this->logger->debug(
			'Received message from device',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'local-api',
				'device' => [
					'identifier' => $this->identifier,
				],
				'message' => [
					'command' => $command->getValue(),
					'data' => $payload,
					'sequence' => $sequenceNr,
					'returnCode' => $returnCode,
				],
			],
		);

		if (
			(
				$command->equalsValue(Types\LocalDeviceCommand::CMD_STATUS)
				|| $command->equalsValue(Types\LocalDeviceCommand::CMD_DP_QUERY)
				|| $command->equalsValue(Types\LocalDeviceCommand::CMD_DP_QUERY_NEW)
			) && $payload !== null
		) {
			try {
				$parsedMessage = $command->equalsValue(
					Types\LocalDeviceCommand::CMD_STATUS,
				) ? $this->schemaValidator->validate(
					$payload,
					$this->getSchemaFilePath(self::DP_STATUS_MESSAGE_SCHEMA_FILENAME),
				) : $this->schemaValidator->validate(
					$payload,
					$this->getSchemaFilePath(self::DP_QUERY_MESSAGE_SCHEMA_FILENAME),
				);
			} catch (MetadataExceptions\InvalidData | MetadataExceptions\Logic | MetadataExceptions\MalformedInput $ex) {
				$this->logger->error(
					'Could not validate payload',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'local-api',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'device' => [
							'identifier' => $this->identifier,
						],
						'message' => [
							'command' => $command->getValue(),
							'payload' => $payload,
						],
					],
				);

				return null;
			}

			$entityOrData = [];

			// v3.4 stuffs it into {"data":{"dps":{"1":true}}, ...}
			if (
				!$parsedMessage->offsetExists('dps')
				&& $parsedMessage->offsetExists('data')
				&& $parsedMessage['data'] instanceof Utils\ArrayHash
				&& $parsedMessage['data']->offsetExists('dps')
			) {
				$parsedMessage['dps'] = $parsedMessage['data']['dps'];
			}

			foreach ((array) $parsedMessage->offsetGet('dps') as $key => $value) {
				if (is_string($value) || is_numeric($value) || is_bool($value)) {
					$entityOrData[] = new Entities\API\DeviceDataPointStatus((string) $key, $value);
				}
			}
		} elseif (
			$command->equalsValue(Types\LocalDeviceCommand::CMD_QUERY_WIFI)
			&& $payload !== null
		) {
			try {
				$parsedMessage = $this->schemaValidator->validate(
					$payload,
					$this->getSchemaFilePath(self::WIFI_QUERY_MESSAGE_SCHEMA_FILENAME),
				);
			} catch (MetadataExceptions\InvalidData | MetadataExceptions\Logic | MetadataExceptions\MalformedInput $ex) {
				$this->logger->error(
					'Could not validate payload',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'local-api',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'device' => [
							'identifier' => $this->identifier,
						],
						'message' => [
							'command' => $command->getValue(),
							'payload' => $payload,
						],
					],
				);

				return null;
			}

			$entityOrData = new Entities\API\DeviceWifiScan(
				$this->identifier,
				array_map(
					static fn ($item): string => strval($item),
					(array) $parsedMessage->offsetGet('ssid_list'),
				),
			);
		} else {
			$entityOrData = $payload;
		}

		return new Entities\API\DeviceRawMessage(
			$this->identifier,
			$command,
			$sequenceNr,
			$hasReturnCode ? $returnCode : null,
			$entityOrData,
		);
	}

	/**
	 * Fill the data structure for the command with the given values
	 *
	 * @param array<string, string|int|float|bool>|null $data
	 */
	private function generateData(
		Types\LocalDeviceCommand $command,
		string $deviceId,
		string|null $gatewayId,
		Types\LocalDeviceType $deviceType,
		string|null $nodeId,
		array|null $data = null,
	): Entities\API\LocalMessagePayload
	{
		$templates = [
			Types\LocalDeviceType::DEFAULT => [
				Types\LocalDeviceCommand::CMD_AP_CONFIG => [
					'template' => [
						'gwId' => '',
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
				Types\LocalDeviceCommand::CMD_CONTROL => [
					'template' => [
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
				Types\LocalDeviceCommand::CMD_STATUS => [
					'template' => [
						'gwId' => '',
						'devId' => '',
					],
				],
				Types\LocalDeviceCommand::CMD_HEART_BEAT => [
					'template' => [
						'gwId' => '',
						'devId' => '',
					],
				],
				Types\LocalDeviceCommand::CMD_DP_QUERY => [
					'template' => [
						'gwId' => '',
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
				Types\LocalDeviceCommand::CMD_CONTROL_NEW => [
					'template' => [
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
				Types\LocalDeviceCommand::CMD_DP_QUERY_NEW => [
					'template' => [
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
				Types\LocalDeviceCommand::CMD_UPDATE_DPS => [
					'template' => [
						'dpId' => [18, 19, 20],
					],
				],
			],
			Types\LocalDeviceType::DEVICE_22 => [
				Types\LocalDeviceCommand::CMD_DP_QUERY => [
					'override' => Types\LocalDeviceCommand::CMD_CONTROL_NEW,
					'template' => [
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
			],
			Types\LocalDeviceType::DEVICE_V34 => [
				Types\LocalDeviceCommand::CMD_CONTROL => [
					'override' => Types\LocalDeviceCommand::CMD_CONTROL_NEW,
					'template' => [
						'protocol' => 5,
						't' => 'int',
						'data' => '',
					],
				],
				Types\LocalDeviceCommand::CMD_DP_QUERY => [
					'override' => Types\LocalDeviceCommand::CMD_DP_QUERY_NEW,
				],
			],
			Types\LocalDeviceType::ZIGBEE => [
				Types\LocalDeviceCommand::CMD_CONTROL => [
					'template' => [
						't' => '',
					],
				],
			],
		];

		$result = null;
		$commandOverride = null;

		if (
			array_key_exists(strval($deviceType->getValue()), $templates)
			&& array_key_exists(intval($command->getValue()), $templates[strval($deviceType->getValue())])
		) {
			$payloadConfiguration = $templates[strval($deviceType->getValue())][intval($command->getValue())];

			if (array_key_exists('template', $payloadConfiguration)) {
				$result = $payloadConfiguration['template'];
			}

			if (
				array_key_exists('override', $payloadConfiguration)
				&& Types\LocalDeviceCommand::isValidValue($payloadConfiguration['override'])
			) {
				$commandOverride = Types\LocalDeviceCommand::get($payloadConfiguration['override']);
			}
		}

		if (!$deviceType->equalsValue(Types\LocalDeviceType::DEFAULT)) {
			if (
				$result === null
				&& array_key_exists(intval($command->getValue()), $templates[Types\LocalDeviceType::DEFAULT])
			) {
				$payloadConfiguration = $templates[Types\LocalDeviceType::DEFAULT][intval($command->getValue())];

				$result = $payloadConfiguration['template'];
			}
		}

		if ($result === null) {
			// I have yet to see a device complain about included but unneeded attribs, but they will
			// complain about missing attribs, so just include them all unless otherwise specified
			$result = ['gwId' => '', 'devId' => '', 'uid' => '', 't' => ''];
		}

		if ($commandOverride === null) {
			$commandOverride = $command;
		}

		if (array_key_exists('gwId', $result) || $gatewayId !== null) {
			$result['gwId'] = $gatewayId ?? $deviceId;
		}

		if (array_key_exists('devId', $result)) {
			$result['devId'] = $deviceId;
		}

		if ($nodeId !== null) {
			$result['cid'] = $nodeId;
		}

		if (array_key_exists('uid', $result)) {
			$result['uid'] = $deviceId; // still use id, no separate uid
		}

		if (array_key_exists('t', $result)) {
			$result['t'] = $result['t'] === 'int'
				? $this->dateTimeFactory->getNow()->getTimestamp()
				: (string) $this->dateTimeFactory->getNow()->getTimestamp();
		}

		if ($command->equalsValue(Types\LocalDeviceCommand::CMD_CONTROL_NEW)) {
			$result['dps'] = ['1' => null, '2' => null, '3' => null];
		}

		if ($data !== null) {
			if (array_key_exists('dpId', $result)) {
				$result['dpId'] = $data;

			} elseif (array_key_exists('data', $result)) {
				$result['data'] = ['dps' => $data];

			} else {
				$result['dps'] = $data;
			}
		} elseif (
			$deviceType->equalsValue(Types\LocalDeviceType::DEVICE_22)
			&& $command->equalsValue(Types\LocalDeviceCommand::CMD_DP_QUERY)
		) {
			$result['dps'] = $this->dpsToRequest;
		}

		try {
			return new Entities\API\LocalMessagePayload(
				$commandOverride,
				str_replace(' ', '', Nette\Utils\Json::encode($result)),
			);
		} catch (Nette\Utils\JsonException $ex) {
			$this->logger->error(
				'Message payload could not be build',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-api',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'device' => [
						'identifier' => $deviceId,
					],
				],
			);

			return new Entities\API\LocalMessagePayload($commandOverride, null);
		}
	}

	/**
	 * Join the payload request parts together
	 *
	 * @param array<int> $payload
	 *
	 * @return array<int>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function stitchPayload(
		int $sequenceNr,
		array $payload,
		Types\LocalDeviceCommand $command,
		string|null $hmacKey,
	): array
	{
		$commandHb = [
			($command->getValue() >> 24) & 0xFF,
			($command->getValue() >> 16) & 0xFF,
			($command->getValue() >> 8) & 0xFF,
			($command->getValue() >> 0) & 0xFF,
		];

		$requestCntHb = [
			($sequenceNr >> 24) & 0xFF,
			($sequenceNr >> 16) & 0xFF,
			($sequenceNr >> 8) & 0xFF,
			($sequenceNr >> 0) & 0xFF,
		];

		$payloadLength = count($payload) + ($hmacKey !== null ? 32 : 8);

		$payloadHbLenHs = [
			($payloadLength >> 24) & 0xFF,
			($payloadLength >> 16) & 0xFF,
			($payloadLength >> 8) & 0xFF,
			($payloadLength >> 0) & 0xFF,
		];

		$headerHb = array_merge(self::MESSAGE_PREFIX, $requestCntHb, $commandHb, $payloadHbLenHs);

		$bufferHb = array_merge($headerHb, $payload);

		if ($hmacKey !== null) {
			$crc = hash_hmac('sha256', pack('C*', ...$bufferHb), $hmacKey, true);

			$crcHb = unpack('C*', $crc);

			if ($crcHb === false) {
				throw new Exceptions\InvalidState('Payload CRC check could not be converted to bytes');
			}
		} else {
			// Calc the CRC of everything except where the CRC goes and the suffix
			$crc = crc32(pack('C*', ...$bufferHb));

			$crcHb = [
				($crc >> 24) & 0xFF,
				($crc >> 16) & 0xFF,
				($crc >> 8) & 0xFF,
				($crc >> 0) & 0xFF,
			];
		}

		return array_merge($bufferHb, $crcHb, self::MESSAGE_SUFFIX);
	}

	/**
	 * @param array<int, int> $needle
	 * @param array<int, int> $haystack
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function findPrefixIndexInMessage(array $needle, array $haystack): int
	{
		$haystackCount = count($haystack);
		$needleCount = count($needle);

		if ($needleCount > $haystackCount) {
			throw new Exceptions\InvalidArgument('Needle array must be smaller than haystack array');
		}

		for ($i = 1; $i <= $haystackCount - $needleCount; $i++) {
			$matchCount = 0;

			for ($j = 0; $j < $needleCount; $j++) {
				if ($needle[$j] === $haystack[$i + $j]) {
					$matchCount++;

					if ($matchCount === $needleCount) {
						return $i - 1;
					}
				}
			}
		}

		return -1;
	}

	/**
	 * @throws Exceptions\LocalApiCall
	 */
	private function getSchemaFilePath(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Tuya\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);

		} catch (Nette\IOException) {
			throw new Exceptions\LocalApiCall('Validation schema for response could not be loaded');
		}

		return $schema;
	}

}
