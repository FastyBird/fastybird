<?php declare(strict_types = 1);

/**
 * LocalApi.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          0.13.0
 *
 * @date           31.08.22
 */

namespace FastyBird\Connector\Tuya\API;

use DateTimeInterface;
use Evenement;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use React\Socket;
use Throwable;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_slice;
use function base64_decode;
use function base64_encode;
use function count;
use function crc32;
use function intval;
use function is_bool;
use function is_numeric;
use function is_string;
use function mb_convert_encoding;
use function md5;
use function openssl_decrypt;
use function openssl_encrypt;
use function ord;
use function pack;
use function React\Async\async;
use function sprintf;
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

	private const SOCKET_PORT = 6_668;

	private const HEARTBEAT_INTERVAL = 7.0;

	private const HEARTBEAT_SEQ_NO = -100;

	private const HEARTBEAT_TIMEOUT = 10.0;

	private const WAIT_FOR_REPLY_TIMEOUT = 5.0;

	public const DP_STATUS_MESSAGE_SCHEMA_FILENAME = 'localapi_dp_query.json';

	public const DP_QUERY_MESSAGE_SCHEMA_FILENAME = 'localapi_dp_status.json';

	public const WIFI_QUERY_MESSAGE_SCHEMA_FILENAME = 'localapi_wifi_query.json';

	private string $gateway;

	private int $sequenceNr = 0;

	private bool $connecting = false;

	private bool $connected = false;

	private DateTimeInterface|null $lastConnectAttempt = null;

	private DateTimeInterface|null $lastHeartbeat = null;

	private DateTimeInterface|null $disconnected = null;

	private DateTimeInterface|null $lost = null;

	/** @var Array<int, Promise\Deferred> */
	private array $messagesListeners = [];

	/** @var Array<int, EventLoop\TimerInterface> */
	private array $messagesListenersTimers = [];

	private EventLoop\TimerInterface|null $heartBeatTimer = null;

	private Socket\ConnectionInterface|null $connection = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly string $identifier,
		string|null $gateway,
		private readonly string $localKey,
		private readonly string $ipAddress,
		private readonly Types\DeviceProtocolVersion $protocolVersion,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->gateway = $gateway ?? $identifier;

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
							if (array_key_exists($message->getSequence(), $this->messagesListeners)) {
								$this->messagesListeners[$message->getSequence()]->resolve($message->getData());

								unset($this->messagesListeners[$message->getSequence()]);

								return;
							}

							if ($message->getCommand()->equalsValue(Types\LocalDeviceCommand::CMD_HEART_BEAT)) {
								$this->lastHeartbeat = $this->dateTimeFactory->getNow();

								$this->logger->debug(
									'Device has replied to heartbeat',
									[
										'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
										'type' => 'localapi-api',
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
										'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
										'type' => 'localapi-api',
										'message' => [
											'data' => $message->getData(),
										],
										'device' => [
											'identifier' => $this->identifier,
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
								'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
								'type' => 'localapi-api',
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
								'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
								'type' => 'localapi-api',
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
										'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
										'type' => 'localapi-api',
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
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'localapi-api',
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

			$deferred->reject();
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
	 * @throws Exceptions\InvalidState
	 */
	public function readStates(): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$sequenceNr = $this->sendRequest(Types\LocalDeviceCommand::get(Types\LocalDeviceCommand::CMD_DP_QUERY));

		$this->messagesListeners[$sequenceNr] = $deferred;

		$this->messagesListenersTimers[$sequenceNr] = $this->eventLoop->addTimer(
			self::WAIT_FOR_REPLY_TIMEOUT,
			async(function () use ($deferred, $sequenceNr): void {
				$deferred->reject(new Exceptions\LocalApiTimeout('Sending command to device failed'));

				unset($this->messagesListeners[$sequenceNr]);
				unset($this->messagesListenersTimers[$sequenceNr]);
			}),
		);

		return $deferred->promise();
	}

	/**
	 * @param Array<string, int|float|string|bool> $states
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function writeStates(array $states): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$sequenceNr = $this->sendRequest(
			Types\LocalDeviceCommand::get(Types\LocalDeviceCommand::CMD_CONTROL),
			$states,
		);

		$this->messagesListeners[$sequenceNr] = $deferred;

		$this->messagesListenersTimers[$sequenceNr] = $this->eventLoop->addTimer(
			self::WAIT_FOR_REPLY_TIMEOUT,
			async(function () use ($deferred, $sequenceNr): void {
				$deferred->reject(new Exceptions\LocalApiTimeout('Sending command to device failed'));

				unset($this->messagesListeners[$sequenceNr]);
				unset($this->messagesListenersTimers[$sequenceNr]);
			}),
		);

		return $deferred->promise();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function writeState(string $idx, int|float|string|bool $value): Promise\PromiseInterface
	{
		return $this->writeStates([$idx => $value]);
	}

	private function lost(): void
	{
		$this->emit('lost');

		$this->lost = $this->dateTimeFactory->getNow();

		$this->disconnect();
	}

	/**
	 * @param Array<string, int|float|string|bool>|null $data
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function sendRequest(
		Types\LocalDeviceCommand $command,
		array|null $data = null,
		int|null $sequenceNr = null,
	): int
	{
		if ($sequenceNr === null) {
			$this->sequenceNr++;

			$payloadSequenceNr = $this->sequenceNr;

		} else {
			$payloadSequenceNr = $sequenceNr;
		}

		$payload = $this->buildPayload($payloadSequenceNr, $command, $data);

		$this->logger->debug(
			'Sending message to device',
			[
				'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
				'type' => 'localapi-api',
				'message' => [
					'command' => $command->getValue(),
					'data' => $data,
					'sequence' => $sequenceNr,
				],
				'device' => [
					'identifier' => $this->identifier,
				],
			],
		);

		$this->connection?->write(pack('C*', ...$payload));

		return $this->sequenceNr;
	}

	/**
	 * @param Array<string, string|int|float|bool>|null $data
	 *
	 * @return Array<int>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function buildPayload(
		int $sequenceNr,
		Types\LocalDeviceCommand $command,
		array|null $data = null,
	): array
	{
		$header = [];

		if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V31)) {
			$payload = $this->generateData($command, $data);

			if ($payload === null) {
				throw new Exceptions\InvalidState('Payload could not be prepared');
			}

			if ($command->equalsValue(Types\LocalDeviceCommand::CMD_CONTROL)) {
				$payload = openssl_encrypt(
					$payload,
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
					(array) unpack('C*', '3.1||'),
					(array) unpack('C*', $this->localKey),
				);

				$hexDigest = md5(pack('C*', ...$preMd5String));
				$hexDigest = Nette\Utils\Strings::substring($hexDigest, 8);
				$hexDigest = Nette\Utils\Strings::substring($hexDigest, 0, 16);

				$header = array_merge(
					(array) unpack('C*', '3.1'),
					(array) unpack('C*', $hexDigest),
				);

				$payload = array_merge($header, (array) unpack('C*', $payload));

			} else {
				$payload = unpack('C*', $payload);
			}

			if ($payload === false) {
				throw new Exceptions\InvalidState('Payload could not be build');
			}

			return $this->stitchPayload($sequenceNr, $payload, $command);
		} elseif ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V33)) {
			if (!$command->equalsValue(Types\LocalDeviceCommand::CMD_DP_QUERY)) {
				$header = array_merge((array) unpack('C*', '3.3'), [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
			}

			$payload = $this->generateData($command, $data);

			if ($payload === null) {
				throw new Exceptions\InvalidState('Payload could not be prepared');
			}

			$payload = openssl_encrypt(
				$payload,
				'AES-128-ECB',
				mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
				OPENSSL_RAW_DATA,
			);

			if ($payload === false) {
				throw new Exceptions\InvalidState('Payload could not be encrypted');
			}

			return $this->stitchPayload(
				$sequenceNr,
				array_merge($header, (array) unpack('C*', $payload)),
				$command,
			);
		}

		throw new Exceptions\InvalidState(
			sprintf('Unknown protocol %s', strval($this->protocolVersion->getValue())),
		);
	}

	/**
	 * @throws Exceptions\LocalApiCall
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function decodePayload(string $data): Entities\API\DeviceRawMessage|null
	{
		$buffer = unpack('C*', $data);

		if ($buffer !== false) {
			$bufferSize = count($buffer);

			$sequenceNr = (int) (($buffer[5] << 24) + ($buffer[6] << 16) + ($buffer[7] << 8) + $buffer[8]);
			$command = (int) (($buffer[9] << 24) + ($buffer[10] << 16) + ($buffer[11] << 8) + $buffer[12]);

			if (!Types\LocalDeviceCommand::isValidValue($command)) {
				$this->logger->error(
					'Received unknown command',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'localapi-api',
						'message' => [
							'command' => $command,
							'sequence' => $sequenceNr,
						],
						'device' => [
							'identifier' => $this->identifier,
						],
					],
				);

				return null;
			}

			$command = Types\LocalDeviceCommand::get($command);

			$size = (int) (($buffer[13] << 24) + ($buffer[14] << 16) + ($buffer[15] << 8) + $buffer[16]);
			$returnCode = (int) (($buffer[17] << 24) + ($buffer[18] << 16) + ($buffer[19] << 8) + $buffer[20]);
			$crc = $buffer[$bufferSize - 7] << 24 + $buffer[$bufferSize - 6] << 16 + $buffer[$bufferSize - 5] << 8 + $buffer[$bufferSize - 4];

			$hasReturnCode = ($returnCode & 0xFFFFFF00) === 0;

			$bodyPart = array_slice($buffer, 0, $bufferSize - 8);

			$bodyPartPacked = pack('C*', ...$bodyPart);

			if (crc32($bodyPartPacked) !== $crc) {
				return null;
			}

			if ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V31)) {
				$data = array_slice($buffer, 20, $bufferSize - 8);

				$payload = null;

				if (($buffer[21] << 8) + $buffer[22] === ord('{')) {
					$payload = pack('C*', ...$data);
				} elseif (
					$buffer[21] === ord('3')
					&& $buffer[22] === ord('.')
					&& $buffer[23] === ord('1')
				) {
					$this->logger->info(
						'Received message from device in version 3.1. This code is untested',
						[
							'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
							'type' => 'localapi-api',
							'device' => [
								'identifier' => $this->identifier,
							],
						],
					);

					$data = array_slice($data, 3); // Remove version header

					// Remove (what I'm guessing, but not confirmed is) 16-bytes of MD5 hex digest of payload
					$data = array_slice($data, 16);

					$payload = openssl_decrypt(
						strval(base64_decode(pack('C*', ...$data), true)),
						'AES-128-ECB',
						mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
						OPENSSL_RAW_DATA,
					);

					if ($payload === false) {
						$this->logger->error(
							'Received message payload could not be decoded',
							[
								'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
								'type' => 'localapi-api',
								'device' => [
									'identifier' => $this->identifier,
								],
							],
						);

						return null;
					}
				}
			} elseif ($this->protocolVersion->equalsValue(Types\DeviceProtocolVersion::VERSION_V33)) {
				$payload = null;

				if ($size > 12) {
					$data = array_slice($buffer, 20, $size + 8 - 20);

					if ($command->equalsValue(Types\LocalDeviceCommand::CMD_STATUS)) {
						$data = array_slice($data, 15);
					}

					$payload = openssl_decrypt(
						pack('C*', ...$data),
						'AES-128-ECB',
						mb_convert_encoding($this->localKey, 'ISO-8859-1', 'UTF-8'),
						OPENSSL_RAW_DATA,
					);

					if ($payload === false) {
						$this->logger->error(
							'Received message payload could not be decoded',
							[
								'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
								'type' => 'localapi-api',
								'device' => [
									'identifier' => $this->identifier,
								],
							],
						);

						return null;
					}
				}
			} else {
				$this->logger->warning(
					'Received message from device with unsupported version',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'localapi-api',
						'message' => [
							'command' => $command->getValue(),
							'sequence' => $sequenceNr,
							'returnCode' => $returnCode,
						],
						'device' => [
							'identifier' => $this->identifier,
						],
					],
				);

				return null;
			}

			$this->logger->debug(
				'Received message from device',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'localapi-api',
					'message' => [
						'command' => $command->getValue(),
						'data' => $payload,
						'sequence' => $sequenceNr,
						'returnCode' => $returnCode,
					],
					'device' => [
						'identifier' => $this->identifier,
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
				$parsedMessage = $command->equalsValue(
					Types\LocalDeviceCommand::CMD_STATUS,
				) ? $this->schemaValidator->validate(
					$payload,
					$this->getSchemaFilePath(self::DP_STATUS_MESSAGE_SCHEMA_FILENAME),
				) : $this->schemaValidator->validate(
					$payload,
					$this->getSchemaFilePath(self::DP_QUERY_MESSAGE_SCHEMA_FILENAME),
				);

				$entityOrData = [];

				foreach ((array) $parsedMessage->offsetGet('dps') as $key => $value) {
					if (is_string($value) || is_numeric($value) || is_bool($value)) {
						$entityOrData[] = new Entities\API\DeviceDataPointStatus((string) $key, $value);
					}
				}
			} elseif (
				$command->equalsValue(Types\LocalDeviceCommand::CMD_QUERY_WIFI)
				&& $payload !== null
			) {
				$parsedMessage = $this->schemaValidator->validate(
					$payload,
					$this->getSchemaFilePath(self::WIFI_QUERY_MESSAGE_SCHEMA_FILENAME),
				);

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

		return null;
	}

	/**
	 * Fill the data structure for the command with the given values
	 *
	 * @param Array<string, string|int|float|bool>|null $data
	 */
	private function generateData(
		Types\LocalDeviceCommand $command,
		array|null $data = null,
	): string|null
	{
		$templates = [
			Types\LocalDeviceCommand::CMD_CONTROL => [
				'devId' => '',
				'uid' => '',
				't' => '',
			],
			Types\LocalDeviceCommand::CMD_STATUS => [
				'gwId' => '',
				'devId' => '',
			],
			Types\LocalDeviceCommand::CMD_HEART_BEAT => [],
			Types\LocalDeviceCommand::CMD_DP_QUERY => [
				'gwId' => '',
				'devId' => '',
				'uid' => '',
				't' => '',
			],
			Types\LocalDeviceCommand::CMD_CONTROL_NEW => [
				'devId' => '',
				'uid' => '',
				't' => '',
			],
			Types\LocalDeviceCommand::CMD_DP_QUERY_NEW => [
				'devId' => '',
				'uid' => '',
				't' => '',
			],
		];

		$result = [];

		if (array_key_exists(intval($command->getValue()), $templates)) {
			$result = $templates[$command->getValue()];
		}

		if (array_key_exists('gwId', $result)) {
			$result['gwId'] = $this->gateway;
		}

		if (array_key_exists('devId', $result)) {
			$result['devId'] = $this->identifier;
		}

		if (array_key_exists('uid', $result)) {
			$result['uid'] = $this->identifier; // still use id, no separate uid
		}

		if (array_key_exists('t', $result)) {
			$result['t'] = (string) $this->dateTimeFactory->getNow()->getTimestamp();
		}

		if ($command->equalsValue(Types\LocalDeviceCommand::CMD_CONTROL_NEW)) {
			$result['dps'] = ['1' => '', '2' => '', '3' => ''];
		}

		if ($data !== null) {
			$result['dps'] = $data;
		}

		try {
			return $result === [] ? '{}' : Nette\Utils\Json::encode($result);
		} catch (Nette\Utils\JsonException $ex) {
			$this->logger->error(
				'Message payload could not be build',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'localapi-api',
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
	}

	/**
	 * Join the payload request parts together
	 *
	 * @param Array<int> $payload
	 *
	 * @return Array<int>
	 */
	private function stitchPayload(
		int $sequenceNr,
		array $payload,
		Types\LocalDeviceCommand $command,
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

		$payloadHb = array_merge($payload, [0, 0, 0, 0, 0, 0, 170, 85]);

		$payloadHbLenHs = [
			(count($payloadHb) >> 24) & 0xFF,
			(count($payloadHb) >> 16) & 0xFF,
			(count($payloadHb) >> 8) & 0xFF,
			(count($payloadHb) >> 0) & 0xFF,
		];

		$headerHb = array_merge([0, 0, 85, 170], $requestCntHb, $commandHb, $payloadHbLenHs);
		$bufferHb = array_merge($headerHb, $payloadHb);

		// Calc the CRC of everything except where the CRC goes and the suffix
		$crc = crc32(pack('C*', ...array_slice($bufferHb, 0, count($bufferHb) - 8)));

		$crcHb = [
			($crc >> 24) & 0xFF,
			($crc >> 16) & 0xFF,
			($crc >> 8) & 0xFF,
			($crc >> 0) & 0xFF,
		];

		return array_merge(
			array_slice($bufferHb, 0, count($bufferHb) - 8),
			$crcHb,
			array_slice($bufferHb, count($bufferHb) - 4),
		);
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
