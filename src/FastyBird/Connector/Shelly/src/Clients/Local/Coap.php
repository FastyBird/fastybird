<?php declare(strict_types = 1);

/**
 * Coap.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           14.07.22
 */

namespace FastyBird\Connector\Shelly\Clients\Local;

use BadMethodCallException;
use Clue\React\Multicast;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Consumers;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Mappers;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\Datagram;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function array_values;
use function count;
use function explode;
use function intval;
use function is_array;
use function mb_convert_encoding;
use function pack;
use function sprintf;
use function str_replace;
use function strval;
use function unpack;
use const DIRECTORY_SEPARATOR;

/**
 * CoAP client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Coap implements Clients\Client
{

	use Nette\SmartObject;

	private const COAP_ADDRESS = '224.0.1.187';

	private const COAP_PORT = 5_683;

	private const STATUS_MESSAGE_CODE = 30;

	private const STATUS_MESSAGE_SCHEMA_FILENAME = 'gen1_coap_status.json';

	private Datagram\SocketInterface|null $server = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly API\Gen1Transformer $transformer,
		private readonly Consumers\Messages $consumer,
		private readonly Mappers\Sensor $sensorMapper,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws BadMethodCallException
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		$factory = new Multicast\Factory($this->eventLoop);

		$this->server = $factory->createReceiver(self::COAP_ADDRESS . ':' . self::COAP_PORT);

		$this->server->on('message', function ($message, $remote): void {
			$this->handlePacket($message, $remote);
		});

		$this->server->on('error', function (Throwable $ex): void {
			$this->logger->error(
				'An error occurred during handling requests',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'coap-client',
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
				'Devices state listener client was terminated',
				$ex->getCode(),
				$ex,
			);
		});

		$this->server->on('close', function (): void {
			$this->logger->info(
				'Client connection was successfully closed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'coap-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);
		});
	}

	public function disconnect(): void
	{
		$this->server?->close();
	}

	public function writeChannelProperty(
		Entities\ShellyDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		return Promise\reject(new Exceptions\InvalidState('Coap client does not support channel writing'));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function handlePacket(string $packet, string $address): void
	{
		$buffer = unpack('C*', $packet);

		if (!is_array($buffer) || count($buffer) < 10) {
			return;
		}

		$pos = 1;

		// Receive messages with ip from proxy
		if ($buffer[1] === 112 && $buffer[2] === 114 && $buffer[3] === 120 && $buffer[4] === 121) {
			$pos = 9;
		}

		$byte = $buffer[$pos];

		$tkl = $byte & 0x0F;

		$code = $buffer[$pos + 1];
		// $messageId = 256 * $buf[3] + $buf[4];

		$pos = $pos + 4 + $tkl;

		if ($code === self::STATUS_MESSAGE_CODE) {
			$byte = $buffer[$pos];
			$totDelta = 0;

			$deviceType = null;
			$deviceIdentifier = null;

			while ($byte !== 0xFF) {
				$delta = $byte >> 4;
				$length = $byte & 0x0F;

				if ($delta === 13) {
					$pos++;
					$delta = $buffer[$pos] + 13;

				} elseif ($delta === 14) {
					$pos += 2;
					$delta = $buffer[$pos - 1] * 256 + $buffer[$pos] + 269;
				}

				$totDelta += $delta;

				if ($length === 13) {
					$pos++;
					$length = $buffer[$pos] + 13;

				} elseif ($length === 14) {
					$pos += 2;
					$length = $buffer[$pos - 1] * 256 + $buffer[$pos] + 269;
				}

				$value = '';
				for ($i = $pos + 1; $i <= $pos + $length; $i++) {
					$value .= pack('C', $buffer[$i]);
				}

				$pos = $pos + $length + 1;

				if ($totDelta === 3_332) {
					[
						$deviceType,
						$deviceIdentifier,
					] = explode('#', mb_convert_encoding($value, 'cp1252', 'utf8')) + [null, null];
				}

				$byte = $buffer[$pos];
			}

			$message = '';

			for ($i = $pos + 1; $i <= count($buffer); $i++) {
				$message .= pack('C', $buffer[$i]);
			}

			$message = mb_convert_encoding($message, 'cp1252', 'utf8');

			$this->logger->debug(
				sprintf(
					'CoAP Code: %d, Type: %s, Id: %s, Payload: %s',
					$code,
					$deviceType,
					$deviceIdentifier,
					str_replace(' ', '', $message),
				),
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'coap-client',
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			if (
				$this->isValidCoapStatusMessage($message)
				&& $deviceType !== null
				&& $deviceIdentifier !== null
			) {
				try {
					$this->handleStatusMessage(
						$address,
						$deviceType,
						$deviceIdentifier,
						$message,
					);
				} catch (Exceptions\ParseMessage $ex) {
					$this->logger->warning(
						'Received message could not be handled',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'coap-client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
						],
					);
				}
			}
		}
	}

	public function isValidCoapStatusMessage(string $message): bool
	{
		$filePath = Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . self::STATUS_MESSAGE_SCHEMA_FILENAME;

		try {
			$schema = Utils\FileSystem::read($filePath);

		} catch (Nette\IOException) {
			return false;
		}

		try {
			$this->schemaValidator->validate($message, $schema);
		} catch (MetadataExceptions\MalformedInput | MetadataExceptions\Logic | MetadataExceptions\InvalidData) {
			return false;
		}

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\ParseMessage
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function handleStatusMessage(
		string $address,
		string $type,
		string $identifier,
		string $message,
	): void
	{
		$filePath = Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . self::STATUS_MESSAGE_SCHEMA_FILENAME;

		try {
			$schema = Utils\FileSystem::read($filePath);

		} catch (Nette\IOException) {
			throw new Exceptions\ParseMessage('Validation schema for message could not be loaded');
		}

		$parsedMessage = $this->schemaValidator->validate($message, $schema);

		if (
			!$parsedMessage->offsetExists('G')
			|| !$parsedMessage['G'] instanceof Utils\ArrayHash
		) {
			throw new Exceptions\ParseMessage('Provided message is not valid');
		}

		$channels = [];

		foreach ($parsedMessage['G'] as $sensorState) {
			if ((is_array($sensorState) || $sensorState instanceof Utils\ArrayHash) && count($sensorState) === 3) {
				[$channel, $sensorIdentifier, $sensorValue] = (array) $sensorState;

				if (!array_key_exists($channel, $channels)) {
					$channels[$channel] = new Entities\Messages\ChannelStatus(
						Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_COAP),
						$channel,
					);
				}

				$property = $this->sensorMapper->findProperty(
					$this->connector->getId(),
					$identifier,
					intval($sensorIdentifier),
				);

				if ($property !== null) {
					$channels[$channel]->addSensor(
						new Entities\Messages\PropertyStatus(
							Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_COAP),
							intval($sensorIdentifier),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								strval($sensorValue),
							),
						),
					);
				}
			}
		}

		$this->consumer->append(
			new Entities\Messages\DeviceStatus(
				Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_COAP),
				$this->connector->getId(),
				$identifier,
				array_values($channels),
			),
		);
	}

}
