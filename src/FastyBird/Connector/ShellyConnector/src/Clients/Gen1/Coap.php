<?php declare(strict_types = 1);

/**
 * Coap.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.37.0
 *
 * @date           14.07.22
 */

namespace FastyBird\ShellyConnector\Clients\Gen1;

use BadMethodCallException;
use Clue\React\Multicast;
use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\Metadata;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\ShellyConnector\API;
use FastyBird\ShellyConnector\Consumers;
use FastyBird\ShellyConnector\Exceptions;
use Nette;
use Psr\Log;
use React\Datagram;
use React\EventLoop;
use RuntimeException;
use Throwable;
use function count;
use function explode;
use function in_array;
use function is_array;
use function mb_convert_encoding;
use function pack;
use function React\Async\async;
use function sprintf;
use function str_replace;
use function unpack;

/**
 * CoAP client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Coap
{

	use Nette\SmartObject;

	private const COAP_ADDRESS = '224.0.1.187';

	private const COAP_PORT = 5_683;

	private const STATUS_MESSAGE_CODE = 30;

	private const DESCRIPTION_MESSAGE_CODE = 69;

	private const AUTOMATIC_DISCOVERY_DELAY = 5;

	private bool $onlyDiscovery = false;

	private Datagram\SocketInterface|null $server = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly MetadataEntities\DevicesModule\Connector $connector,
		private readonly API\Gen1Validator $validator,
		private readonly API\Gen1Parser $parser,
		private readonly Consumers\Messages $consumer,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DevicesModuleExceptions\Terminate
	 */
	public function discover(): void
	{
		if ($this->server === null) {
			$this->logger->warning(
				'Client is not running, discovery process could not be processed',
				[
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'coap-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			if ($this->onlyDiscovery) {
				throw new DevicesModuleExceptions\Terminate(
					'Discovery client is not created, discovery could not be performed',
				);
			}

			return;
		}

		$message = pack('C*', 80, 1, 0, 10, 179, 99, 105, 116, 1, 100, 255);

		$this->logger->debug(
			'Sending discover devices packet',
			[
				'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
				'type' => 'coap-client',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->server->send($message, self::COAP_ADDRESS . ':' . self::COAP_PORT);
	}

	/**
	 * @throws BadMethodCallException
	 * @throws RuntimeException
	 */
	public function connect(bool $onlyDiscovery = false): void
	{
		$this->onlyDiscovery = $onlyDiscovery;

		$factory = new Multicast\Factory($this->eventLoop);

		$this->server = $factory->createReceiver(self::COAP_ADDRESS . ':' . self::COAP_PORT);

		$this->server->on('message', function ($message, $remote): void {
			$this->handlePacket($message, $remote);
		});

		$this->server->on('error', function (Throwable $ex): void {
			$this->logger->error(
				'An error occurred during handling requests',
				[
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'coap-client',
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
				'Devices state listener client was terminated',
				$ex->getCode(),
				$ex,
			);
		});

		$this->server->on('close', function (): void {
			$this->logger->info(
				'Client connection was successfully closed',
				[
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'coap-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);
		});

		if ($onlyDiscovery) {
			$this->eventLoop->futureTick(function (): void {
				$this->discover();
			});

		} else {
			$this->eventLoop->addTimer(
				self::AUTOMATIC_DISCOVERY_DELAY,
				async(function (): void {
					$this->discover();
				}),
			);
		}
	}

	public function disconnect(): void
	{
		$this->server?->close();
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

		if (in_array($code, [self::STATUS_MESSAGE_CODE, self::DESCRIPTION_MESSAGE_CODE], true)) {
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
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'coap-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			if (
				$code === self::STATUS_MESSAGE_CODE
				&& $this->validator->isValidCoapStatusMessage($message)
				&& $deviceType !== null
				&& $deviceIdentifier !== null
				&& !$this->onlyDiscovery
			) {
				try {
					$this->consumer->append(
						$this->parser->parseCoapStatusMessage(
							$this->connector->getId(),
							$address,
							$deviceType,
							$deviceIdentifier,
							$message,
						),
					);
				} catch (Exceptions\ParseMessage $ex) {
					$this->logger->warning(
						'Received message could not be parsed into entity',
						[
							'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
							'type' => 'coap-client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);
				}
			} elseif (
				$code === self::DESCRIPTION_MESSAGE_CODE
				&& $this->validator->isValidCoapDescriptionMessage($message)
				&& $deviceType !== null
				&& $deviceIdentifier !== null
			) {
				try {
					$this->consumer->append(
						$this->parser->parseCoapDescriptionMessage(
							$this->connector->getId(),
							$address,
							$deviceType,
							$deviceIdentifier,
							$message,
						),
					);
				} catch (Exceptions\ParseMessage $ex) {
					$this->logger->warning(
						'Received message could not be parsed into entity',
						[
							'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
							'type' => 'coap-client',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);
				}
			}
		}
	}

}
