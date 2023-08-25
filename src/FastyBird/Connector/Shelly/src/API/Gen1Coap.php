<?php declare(strict_types = 1);

/**
 * Coap.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           14.07.22
 */

namespace FastyBird\Connector\Shelly\API;

use BadMethodCallException;
use Clue\React\Multicast;
use Evenement;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\Datagram;
use React\EventLoop;
use RuntimeException;
use Throwable;
use function count;
use function explode;
use function is_array;
use function mb_convert_encoding;
use function pack;
use function sprintf;
use function str_replace;
use function unpack;
use const DIRECTORY_SEPARATOR;

/**
 * Generation 1 device CoAP interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen1Coap implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const COAP_ADDRESS = '224.0.1.187';

	private const COAP_PORT = 5_683;

	private const STATUS_MESSAGE_CODE = 30;

	private const STATUS_MESSAGE_SCHEMA_FILENAME = 'gen1_coap_state.json';

	private Datagram\SocketInterface|null $server = null;

	public function __construct(
		private readonly Helpers\Entity $entityHelper,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
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
			$this->emit(
				'error',
				[new Exceptions\CoapError('An error occurred during handling requests', $ex->getCode(), $ex)],
			);
		});

		$this->server->on('close', function (): void {
			$this->logger->debug(
				'Client CoAP connection was successfully closed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'gen1-coap-api',
				],
			);
		});
	}

	public function disconnect(): void
	{
		$this->server?->close();
	}

	private function handlePacket(string $packet, string $remote): void
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
					'type' => 'gen1-coap-api',
				],
			);

			if (
				$this->isValidCoapStatusMessage($message)
				&& $deviceType !== null
				&& $deviceIdentifier !== null
			) {
				try {
					$this->handleStatusMessage($deviceIdentifier, $message, $remote);
				} catch (Exceptions\CoapError $ex) {
					$this->emit(
						'error',
						[new Exceptions\CoapError('Received message could not be handled', $ex->getCode(), $ex)],
					);
				}
			}
		}
	}

	private function isValidCoapStatusMessage(string $message): bool
	{
		try {
			$this->validatePayload($message, self::STATUS_MESSAGE_SCHEMA_FILENAME);

		} catch (Exceptions\CoapError) {
			return false;
		}

		return true;
	}

	/**
	 * @throws Exceptions\CoapError
	 */
	private function handleStatusMessage(
		string $deviceIdentifier,
		string $message,
		string $remote,
	): void
	{
		$parsedMessage = $this->validatePayload($message, self::STATUS_MESSAGE_SCHEMA_FILENAME);

		if (
			!$parsedMessage->offsetExists('G')
			|| !$parsedMessage['G'] instanceof Utils\ArrayHash
		) {
			throw new Exceptions\CoapError('Provided message is not valid');
		}

		$statuses = [];

		foreach ($parsedMessage['G'] as $status) {
			if ((is_array($status) || $status instanceof Utils\ArrayHash) && count($status) === 3) {
				[$blockIdentifier, $sensorIdentifier, $sensorValue] = (array) $status;

				$statuses[] = [
					'block' => $blockIdentifier,
					'sensor' => $sensorIdentifier,
					'value' => $sensorValue,
				];
			}
		}

		try {
			$this->emit('message', [
				$this->entityHelper->create(
					Entities\API\Gen1\ReportDeviceState::class,
					[
						'identifier' => $deviceIdentifier,
						'ip_address' => $remote,
						'states' => $statuses,
					],
				),
			]);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\CoapError('Could not map payload to entity', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\CoapError
	 */
	private function validatePayload(
		string $payload,
		string $schemaFilename,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		try {
			return $this->schemaValidator->validate(
				$payload,
				$this->getSchema($schemaFilename),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			if ($throw) {
				throw new Exceptions\CoapError(
					'Could not validate received response payload',
					$ex->getCode(),
					$ex,
				);
			}

			return false;
		}
	}

	/**
	 * @throws Exceptions\CoapError
	 */
	private function getSchema(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);

		} catch (Nette\IOException) {
			throw new Exceptions\CoapError('Validation schema for payload could not be loaded');
		}

		return $schema;
	}

}
