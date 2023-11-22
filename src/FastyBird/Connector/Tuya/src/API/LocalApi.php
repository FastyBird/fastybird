<?php declare(strict_types = 1);

/**
 * LocalApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
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
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Services;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Connector\Tuya\ValueObjects;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use Nette\Utils;
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

	public const DP_QUERY_MESSAGE_SCHEMA_FILENAME = 'localapi_dp_query.json';

	public const DP_STATE_MESSAGE_SCHEMA_FILENAME = 'localapi_dp_state.json';

	public const WIFI_QUERY_MESSAGE_SCHEMA_FILENAME = 'localapi_wifi_query.json';

	private const SOCKET_PORT = 6_668;

	private const HEARTBEAT_INTERVAL = 7.0;

	private const HEARTBEAT_SEQ_NO = -100;

	private const HEARTBEAT_TIMEOUT = 10.0;

	private const WAIT_FOR_REPLY_TIMEOUT = 5.0;

	private const MESSAGE_PREFIX = [0, 0, 85, 170];

	private const MESSAGE_SUFFIX = [0, 0, 170, 85];

	private const NO_PROTOCOL_HEADER_COMMANDS = [
		Types\LocalDeviceCommand::DP_QUERY,
		Types\LocalDeviceCommand::DP_QUERY_NEW,
		Types\LocalDeviceCommand::UPDATE_DPS,
		Types\LocalDeviceCommand::HEART_BEAT,
		Types\LocalDeviceCommand::SESS_KEY_NEG_START,
		Types\LocalDeviceCommand::SESS_KEY_NEG_RESP,
		Types\LocalDeviceCommand::SESS_KEY_NEG_FINISH,
	];

	private Types\LocalDeviceType $deviceType;

	private int $sequenceNr = 0;

	private bool $connecting = false;

	private bool $connected = false;

	private bool $waitingForReading = false;

	/** @var array<string|int, int|float|string|null> */
	private array $dpsToRequest = [];

	/** @var array<string, string> */
	private array $validationSchemas = [];

	private DateTimeInterface|null $lastConnectAttempt = null;

	private DateTimeInterface|null $lastHeartbeat = null;

	private DateTimeInterface|null $disconnected = null;

	private DateTimeInterface|null $lost = null;
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	/** @var array<int, Promise\Deferred<string|array<Entities\API\DeviceDataPointState>|Entities\API\LocalDeviceWifiScan|Types\LocalDeviceError|null>> */
	private array $messagesListeners = [];

	/** @var array<int, EventLoop\TimerInterface> */
	private array $messagesListenersTimers = [];

	private EventLoop\TimerInterface|null $heartBeatTimer = null;

	private Socket\ConnectionInterface|null $connection = null;

	/**
	 * @param array<ValueObjects\LocalChild> $children
	 */
	public function __construct(
		private readonly string $identifier,
		private readonly string|null $gateway,
		private readonly string|null $nodeId,
		private readonly string $localKey,
		private readonly string $ipAddress,
		private readonly Types\DeviceProtocolVersion $protocolVersion,
		private readonly array $children,
		private readonly Services\SocketClientFactory $socketClientFactory,
		private readonly Helpers\Entity $entityHelper,
		private readonly Tuya\Logger $logger,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
		$this->deviceType = $this->nodeId !== null
			? Types\LocalDeviceType::get(Types\LocalDeviceType::ZIGBEE)
			: Types\LocalDeviceType::get(Types\LocalDeviceType::DEFAULT);

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V32)) {
			// 3.2 behaves like 3.3 with device22
			$this->deviceType = Types\LocalDeviceType::get(Types\LocalDeviceType::DEVICE_22);

		} elseif ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V34)) {
			$this->deviceType = Types\LocalDeviceType::get(Types\LocalDeviceType::DEVICE_V34);

		} elseif ($this->deviceType->equalsValue(Types\LocalDeviceType::DEVICE_V34)) {
			$this->deviceType = Types\LocalDeviceType::get(Types\LocalDeviceType::DEFAULT);
		}

		if ($this->children !== []) {
			$this->deviceType = Types\LocalDeviceType::get(Types\LocalDeviceType::GATEWAY);
		}
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function connect(): Promise\PromiseInterface
	{
		$this->messagesListeners = [];
		$this->messagesListenersTimers = [];

		$this->connection = null;
		$this->connecting = true;
		$this->connected = false;

		$this->heartBeatTimer = null;
		$this->lastHeartbeat = null;

		$this->lastConnectAttempt = $this->dateTimeFactory->getNow();
		$this->lost = null;
		$this->disconnected = null;

		$deferred = new Promise\Deferred();

		try {
			$this->socketClientFactory
				->create()
				->connect($this->ipAddress . ':' . self::SOCKET_PORT)
				->then(function (Socket\ConnectionInterface $connection) use ($deferred): void {
					$this->connection = $connection;
					$this->connecting = false;
					$this->connected = true;

					$this->lost = null;
					$this->disconnected = null;

					$this->connection->on('data', function ($chunk): void {
						$message = $this->decodePayload($chunk);

						if ($message !== null) {
							if (
								$message->getError() !== null
								&& $message->getError()->equalsValue(Types\LocalDeviceError::DEVICE_TYPE)
								&& $message->getCommand()->equalsValue(Types\LocalDeviceCommand::DP_QUERY)
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

								$this->eventLoop->cancelTimer(
									$this->messagesListenersTimers[$message->getSequence()],
								);

								unset($this->messagesListeners[$message->getSequence()]);
								unset($this->messagesListenersTimers[$message->getSequence()]);

								return;
							}

							if ($message->getCommand()->equalsValue(Types\LocalDeviceCommand::HEART_BEAT)) {
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

							if ($message->getCommand()->equalsValue(Types\LocalDeviceCommand::STATUS)) {
								$this->logger->debug(
									'Device has reported its state',
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
						$this->emit(
							'error',
							[new Exceptions\LocalApiError(
								'An error occurred on device connection',
								$ex->getCode(),
								$ex,
							)],
						);

						$this->lost();
					});

					$this->connection->on('close', function (): void {
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

						$this->disconnect();

						$this->emit('disconnected');
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
									Types\LocalDeviceCommand::get(Types\LocalDeviceCommand::HEART_BEAT),
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

					$deferred->resolve(true);
				})
				->catch(function (Throwable $ex) use ($deferred): void {
					$this->connection = null;

					$this->connecting = false;
					$this->connected = false;

					$deferred->reject($ex);
				});
		} catch (Throwable $ex) {
			$this->connection = null;

			$this->connecting = false;
			$this->connected = false;

			$deferred->reject(new Exceptions\LocalApiError('Could not create connector', $ex->getCode(), $ex));
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
	 * @return Promise\PromiseInterface<string|array<Entities\API\DeviceDataPointState>|Entities\API\LocalDeviceWifiScan|Types\LocalDeviceError|null>
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
				return Promise\reject(
					new Exceptions\LocalApiError('Provided child identifier is not registered under parent'),
				);
			}
		}

		try {
			$sequenceNr = $this->sendRequest(
				Types\LocalDeviceCommand::get(Types\LocalDeviceCommand::DP_QUERY),
				null,
				null,
				$localChild,
			);
		} catch (Exceptions\LocalApiCall | Exceptions\LocalApiError $ex) {
			return Promise\reject($ex);
		}

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
			->catch(function (): void {
				$this->waitingForReading = false;
			});

		return $promise;
	}

	/**
	 * @param array<string, int|float|string|bool> $states
	 *
	 * @return Promise\PromiseInterface<string|array<Entities\API\DeviceDataPointState>|Entities\API\LocalDeviceWifiScan|Types\LocalDeviceError|null>
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
				return Promise\reject(
					new Exceptions\LocalApiError('Provided child identifier is not registered under parent'),
				);
			}
		}

		try {
			$sequenceNr = $this->sendRequest(
				Types\LocalDeviceCommand::get(Types\LocalDeviceCommand::CONTROL),
				$states,
				null,
				$localChild,
			);
		} catch (Exceptions\LocalApiCall | Exceptions\LocalApiError $ex) {
			return Promise\reject($ex);
		}

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
	 * @return Promise\PromiseInterface<string|array<Entities\API\DeviceDataPointState>|Entities\API\LocalDeviceWifiScan|Types\LocalDeviceError|null>
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
	 * @throws Exceptions\LocalApiCall
	 */
	public function detectAvailableDps(): array
	{
		$dpsCache = [];
		$ranges = [[2, 11], [11, 21], [21, 31], [100, 111]];

		foreach ($ranges as $dpsRange) {
			$this->dpsToRequest = ['1' => null];
			$this->addDpsToRequest(range($dpsRange[0], $dpsRange[1]));

			try {
				/** @var array<Entities\API\DeviceDataPointState>|Types\LocalDeviceError $deviceStates */
				$deviceStates = await($this->readStates());
			} catch (Throwable $ex) {
				throw new Exceptions\LocalApiCall('Reading state from device failed', $ex->getCode(), $ex);
			}

			if (is_array($deviceStates)) {
				foreach ($deviceStates as $deviceState) {
					$dpsCache[$deviceState->getCode()] = null;
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
	 * @throws Exceptions\LocalApiCall
	 * @throws Exceptions\LocalApiError
	 */
	private function sendRequest(
		Types\LocalDeviceCommand $command,
		array|null $data = null,
		int|null $sequenceNr = null,
		ValueObjects\LocalChild|null $child = null,
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
	 * @throws Exceptions\LocalApiCall
	 * @throws Exceptions\LocalApiError
	 */
	private function buildPayload(
		int $sequenceNr,
		Types\LocalDeviceCommand $command,
		ValueObjects\LocalChild|null $child = null,
		array|null $data = null,
	): array
	{
		$header = [];
		$hmacKey = null;

		$deviceType = $child?->getType() ?? $this->deviceType;
		$deviceId = $child?->getIdentifier() ?? $this->identifier;
		$gatewayId = $child !== null ? $this->identifier : $this->gateway;
		$nodeId = $child?->getNodeId() ?? $this->nodeId;

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V31)) {
			$message = $this->generateData($command, $deviceId, $gatewayId, $deviceType, $nodeId, $data);

			if ($message->getPayload() === null) {
				throw new Exceptions\LocalApiError('Payload could not be prepared');
			}

			if ($message->getCommand()->equalsValue(Types\LocalDeviceCommand::CONTROL)) {
				$payload = openssl_encrypt(
					$message->getPayload(),
					'AES-128-ECB',
					mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
					OPENSSL_RAW_DATA,
				);

				if ($payload === false) {
					throw new Exceptions\LocalApiError('Payload could not be encrypted');
				}

				$payload = base64_encode($payload);

				$preMd5String = array_merge(
					(array) unpack('C*', 'data='),
					(array) unpack('C*', $payload),
					(array) unpack('C*', '||lpv='),
					(array) unpack('C*', Types\DeviceProtocolVersion::V31 . '||'),
					(array) unpack('C*', $this->localKey),
				);

				$hexDigest = md5(pack('C*', ...$preMd5String));
				$hexDigest = Utils\Strings::substring($hexDigest, 8);
				$hexDigest = Utils\Strings::substring($hexDigest, 0, 16);

				$header = array_merge(
					(array) unpack('C*', Types\DeviceProtocolVersion::V31),
					(array) unpack('C*', $hexDigest),
				);

				$payload = array_merge($header, (array) unpack('C*', $payload));

			} else {
				$payload = unpack('C*', $message->getPayload());
			}

			if ($payload === false) {
				throw new Exceptions\LocalApiError('Payload could not be build');
			}

			return $this->stitchPayload($sequenceNr, $payload, $command, $hmacKey);
		} elseif (
			$this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V32)
			|| $this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V33)
			|| $this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V34)
		) {
			$message = $this->generateData($command, $deviceId, $gatewayId, $deviceType, $nodeId, $data);

			if ($message->getPayload() === null) {
				throw new Exceptions\LocalApiError('Payload could not be prepared');
			}

			if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V34)) {
				if (!in_array($message->getCommand()->getValue(), self::NO_PROTOCOL_HEADER_COMMANDS, true)) {
					$header = array_merge(
						(array) unpack('C*', $this->protocolVersion->getValue()),
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
					throw new Exceptions\LocalApiError('Payload could not be encrypted');
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
					throw new Exceptions\LocalApiError('Payload could not be encrypted');
				}

				if (!in_array($message->getCommand()->getValue(), self::NO_PROTOCOL_HEADER_COMMANDS, true)) {
					$header = array_merge(
						(array) unpack('C*', $this->protocolVersion->getValue()),
						[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
					);
				}

				$payload = array_merge($header, (array) unpack('C*', $payload));
			}

			return $this->stitchPayload($sequenceNr, $payload, $message->getCommand(), $hmacKey);
		}

		throw new Exceptions\LocalApiError(
			sprintf('Unknown protocol %s', $this->protocolVersion->getValue()),
		);
	}

	/**
	 * @throws Exceptions\LocalApiCall
	 */
	private function decodePayload(string $data): Entities\API\LocalDeviceMessage|null
	{
		$headerLength = 16; // 4B prefix + 4B sequence nr + 4B command + 4B data length
		$footerLength = 8; // 4B CRC check + 4B suffix

		$useHmac = false;

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V34)) {
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
			throw new Exceptions\LocalApiCall(
				'Could not parse message parts - sequence nr, command & length',
				$ex->getCode(),
				$ex,
			);
		}

		if ($prefix !== self::MESSAGE_PREFIX) {
			throw new Exceptions\LocalApiCall('Message prefix is not as expected');
		}

		if ($dataLength > 1_000) {
			throw new Exceptions\LocalApiCall(
				'Header claims the packet size is over 1000 bytes!  It is most likely corrupt',
			);
		}

		if (!Types\LocalDeviceCommand::isValidValue($command)) {
			throw new Exceptions\LocalApiCall('Received unknown command');
		}

		$command = Types\LocalDeviceCommand::get($command);

		if (count($buffer) < $headerLength + $footerLength) {
			throw new Exceptions\LocalApiCall('Not enough data to unpack payload');
		}

		try {
			$returnCode = [$buffer[16], $buffer[17], $buffer[18], $buffer[19]];
			$returnCode = Math\BigInteger::fromBytes(pack('C*', ...$returnCode), false)->toInt();

			$footer = array_slice($buffer, -$footerLength);
			$crc = array_slice($footer, 0, count($footer) - 4);

			$crc = $useHmac ? pack('C*', ...$crc) : Math\BigInteger::fromBytes(pack('C*', ...$crc), false)->toInt();

			$suffix = array_slice($buffer, -4);

		} catch (Math\Exception\MathException $ex) {
			throw new Exceptions\LocalApiCall('Could not parse message parts - return code & crc', $ex->getCode(), $ex);
		}

		$hasReturnCode = ($returnCode & 0xFFFFFF00) === 0;

		$headerWithDataPart = array_slice($buffer, 0, $bufferSize - $footerLength);

		$calculatedCrc = $useHmac
			? hash_hmac('sha256', pack('C*', ...$headerWithDataPart), $this->localKey, true)
			: crc32(pack('C*', ...$headerWithDataPart));

		if ($calculatedCrc !== $crc) {
			throw new Exceptions\LocalApiCall($useHmac ? 'HMAC checksum is wrong' : 'CRC checksum is wrong');
		}

		if ($suffix !== self::MESSAGE_SUFFIX) {
			throw new Exceptions\LocalApiCall('Message suffix is not as expected');
		}

		$dataPart = array_values(array_slice($buffer, 20, $dataLength + $footerLength - 20));

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V34)) {
			$dataPart = openssl_decrypt(
				pack('C*', ...$dataPart),
				'AES-128-ECB',
				mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
				OPENSSL_RAW_DATA,
			);

			if ($dataPart === false) {
				throw new Exceptions\LocalApiCall('Received message data could not be decoded');
			}

			$dataPart = (array) unpack('C*', $dataPart);
		}

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V31)) {
			$payload = null;

			if (
				count($dataPart) > 2
				&& in_array(ord('{'), [$dataPart[0], $dataPart[1]], true)
			) {
				$payload = pack('C*', ...$dataPart);
			} elseif (
				count($dataPart) > 3
				&& unpack('C*', Types\DeviceProtocolVersion::V31) === [$dataPart[0], $dataPart[1], $dataPart[2]]
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
					throw new Exceptions\LocalApiCall('Received message payload could not be decoded');
				}
			}
		} elseif (
			$this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V32)
			|| $this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V33)
			|| $this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V34)
		) {
			$payload = null;

			if (count($dataPart) > 12) {
				if (
					array_values((array) unpack(
						'C*',
						Types\DeviceProtocolVersion::V32,
					)) === [$dataPart[0], $dataPart[1], $dataPart[2]]
					|| array_values((array) unpack(
						'C*',
						Types\DeviceProtocolVersion::V33,
					)) === [$dataPart[0], $dataPart[1], $dataPart[2]]
					|| array_values((array) unpack(
						'C*',
						Types\DeviceProtocolVersion::V34,
					)) === [$dataPart[0], $dataPart[1], $dataPart[2]]
				) {
					$dataPart = array_slice($dataPart, 3); // Remove version header
				} elseif ($this->deviceType->equalsValue(Types\LocalDeviceType::DEVICE_22)) {
					$dataPart = array_slice($dataPart, 3); // Remove version header
				}

				if (!$this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::V34)) {
					if ($command->equalsValue(Types\LocalDeviceCommand::STATUS)) {
						$dataPart = array_slice($dataPart, 12);
					}

					$payload = openssl_decrypt(
						pack('C*', ...$dataPart),
						'AES-128-ECB',
						mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
						OPENSSL_RAW_DATA,
					);

					if ($payload === false) {
						throw new Exceptions\LocalApiCall('Received message payload could not be decoded');
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
							$this->deviceType->getValue(),
						),
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'local-api',
							'device' => [
								'identifier' => $this->identifier,
							],
						],
					);

					return $this->createEntity(
						Entities\API\LocalDeviceMessage::class,
						Utils\ArrayHash::from([
							'identifier' => $this->identifier,
							'command' => $command->getValue(),
							'sequence' => $sequenceNr,
							'return_code' => $hasReturnCode ? $returnCode : null,
							'data' => null,
							'error' => Types\LocalDeviceError::DEVICE_TYPE,
						]),
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
				return $this->createEntity(
					Entities\API\LocalDeviceMessage::class,
					Utils\ArrayHash::from([
						'identifier' => $this->identifier,
						'command' => $command->getValue(),
						'sequence' => $sequenceNr,
						'return_code' => $hasReturnCode ? $returnCode : null,
						'data' => 'Data received from device are invalid',
						'error' => Types\LocalDeviceError::PAYLOAD,
					]),
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
				$command->equalsValue(Types\LocalDeviceCommand::STATUS)
				|| $command->equalsValue(Types\LocalDeviceCommand::DP_QUERY)
				|| $command->equalsValue(Types\LocalDeviceCommand::DP_QUERY_NEW)
			) && $payload !== null
		) {
			$parsedMessage = $command->equalsValue(Types\LocalDeviceCommand::STATUS)
				? $this->validateData($payload, self::DP_QUERY_MESSAGE_SCHEMA_FILENAME)
				: $this->validateData($payload, self::DP_STATE_MESSAGE_SCHEMA_FILENAME);

			$entityData = [];

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
					$entityData[] = [
						'code' => (string) $key,
						'value' => $value,
					];
				}
			}
		} elseif (
			$command->equalsValue(Types\LocalDeviceCommand::QUERY_WIFI)
			&& $payload !== null
		) {
			$parsedMessage = $this->validateData($payload, self::WIFI_QUERY_MESSAGE_SCHEMA_FILENAME);

			$entityData = [
				'identifier' => $this->identifier,
				'ssids' => array_map(
					static fn ($item): string => strval($item),
					(array) $parsedMessage->offsetGet('ssid_list'),
				),
			];
		} else {
			$entityData = $payload;
		}

		return $this->createEntity(
			Entities\API\LocalDeviceMessage::class,
			Utils\ArrayHash::from([
				'identifier' => $this->identifier,
				'command' => $command->getValue(),
				'sequence' => $sequenceNr,
				'return_code' => $hasReturnCode ? $returnCode : null,
				'data' => $entityData,
			]),
		);
	}

	/**
	 * Fill the data structure for the command with the given values
	 *
	 * @param array<string, string|int|float|bool>|null $data
	 *
	 * @throws Exceptions\LocalApiCall
	 * @throws Exceptions\LocalApiError
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
				Types\LocalDeviceCommand::AP_CONFIG => [
					'template' => [
						'gwId' => '',
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
				Types\LocalDeviceCommand::CONTROL => [
					'template' => [
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
				Types\LocalDeviceCommand::STATUS => [
					'template' => [
						'gwId' => '',
						'devId' => '',
					],
				],
				Types\LocalDeviceCommand::HEART_BEAT => [
					'template' => [
						'gwId' => '',
						'devId' => '',
					],
				],
				Types\LocalDeviceCommand::DP_QUERY => [
					'template' => [
						'gwId' => '',
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
				Types\LocalDeviceCommand::CONTROL_NEW => [
					'template' => [
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
				Types\LocalDeviceCommand::DP_QUERY_NEW => [
					'template' => [
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
				Types\LocalDeviceCommand::UPDATE_DPS => [
					'template' => [
						'dpId' => [18, 19, 20],
					],
				],
			],
			Types\LocalDeviceType::DEVICE_22 => [
				Types\LocalDeviceCommand::DP_QUERY => [
					'override' => Types\LocalDeviceCommand::CONTROL_NEW,
					'template' => [
						'devId' => '',
						'uid' => '',
						't' => '',
					],
				],
			],
			Types\LocalDeviceType::DEVICE_V34 => [
				Types\LocalDeviceCommand::CONTROL => [
					'override' => Types\LocalDeviceCommand::CONTROL_NEW,
					'template' => [
						'protocol' => 5,
						't' => 'int',
						'data' => '',
					],
				],
				Types\LocalDeviceCommand::DP_QUERY => [
					'override' => Types\LocalDeviceCommand::DP_QUERY_NEW,
				],
			],
			Types\LocalDeviceType::ZIGBEE => [
				Types\LocalDeviceCommand::CONTROL => [
					'template' => [
						't' => '',
					],
				],
			],
		];

		$result = null;
		$commandOverride = null;

		if (
			array_key_exists($deviceType->getValue(), $templates)
			&& array_key_exists($command->getValue(), $templates[$deviceType->getValue()])
		) {
			$payloadConfiguration = $templates[$deviceType->getValue()][$command->getValue()];

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

		if ($command->equalsValue(Types\LocalDeviceCommand::CONTROL_NEW)) {
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
			&& $command->equalsValue(Types\LocalDeviceCommand::DP_QUERY)
		) {
			$result['dps'] = $this->dpsToRequest;
		}

		try {
			return $this->createEntity(
				Entities\API\LocalMessagePayload::class,
				Utils\ArrayHash::from([
					'command' => $commandOverride->getValue(),
					'payload' => str_replace(' ', '', Utils\Json::encode($result)),
				]),
			);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\LocalApiError('Message payload could not be build', $ex->getCode(), $ex);
		}
	}

	/**
	 * Join the payload request parts together
	 *
	 * @param array<int> $payload
	 *
	 * @return array<int>
	 *
	 * @throws Exceptions\LocalApiError
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
				throw new Exceptions\LocalApiError('Payload CRC check could not be converted to bytes');
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
	 * @throws Exceptions\LocalApiCall
	 */
	private function findPrefixIndexInMessage(array $needle, array $haystack): int
	{
		$haystackCount = count($haystack);
		$needleCount = count($needle);

		if ($needleCount > $haystackCount) {
			throw new Exceptions\LocalApiCall('Needle array must be smaller than haystack array');
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
	 * @template T of Entities\API\Entity
	 *
	 * @param class-string<T> $entity
	 *
	 * @return T
	 *
	 * @throws Exceptions\LocalApiCall
	 */
	private function createEntity(string $entity, Utils\ArrayHash $data): Entities\API\Entity
	{
		try {
			return $this->entityHelper->create(
				$entity,
				(array) Utils\Json::decode(Utils\Json::encode($data), Utils\Json::FORCE_ARRAY),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\LocalApiCall('Could not map data to entity', $ex->getCode(), $ex);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\LocalApiCall(
				'Could not create entity from data',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\LocalApiCall
	 */
	private function validateData(
		string $data,
		string $schemaFilename,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		try {
			return $this->schemaValidator->validate(
				$data,
				$this->getSchema($schemaFilename),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			if ($throw) {
				throw new Exceptions\LocalApiCall(
					'Could not validate received response payload',
					$ex->getCode(),
					$ex,
				);
			}

			return false;
		}
	}

	/**
	 * @throws Exceptions\LocalApiCall
	 */
	private function getSchema(string $schemaFilename): string
	{
		$key = md5($schemaFilename);

		if (!array_key_exists($key, $this->validationSchemas)) {
			try {
				$this->validationSchemas[$key] = Utils\FileSystem::read(
					Tuya\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
				);

			} catch (Nette\IOException) {
				throw new Exceptions\LocalApiCall('Validation schema for data could not be loaded');
			}
		}

		return $this->validationSchemas[$key];
	}

}
