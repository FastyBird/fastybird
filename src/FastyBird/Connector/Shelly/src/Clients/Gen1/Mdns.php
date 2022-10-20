<?php declare(strict_types = 1);

/**
 * Mdns.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.37.0
 *
 * @date           14.07.22
 */

namespace FastyBird\Connector\Shelly\Clients\Gen1;

use BadMethodCallException;
use Clue\React\Multicast;
use FastyBird\Connector\Shelly\Consumers;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Entities\Clients\MdnsResult;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\Datagram;
use React\Dns;
use React\EventLoop;
use RuntimeException;
use function count;
use function is_array;
use function is_string;
use function preg_match;

/**
 * mDNS client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Mdns
{

	use Nette\SmartObject;

	private const DNS_ADDRESS = '224.0.0.251';

	private const DNS_PORT = 5_353;

	private const MATCH_NAME = '/^(?P<devtype>shelly.+)-(?P<id>[0-9A-Fa-f]+)._(http|shelly)._tcp.local$/';

	private MdnsResultStorage $searchResult;

	private Dns\Protocol\Parser $parser;

	private Dns\Protocol\BinaryDumper $dumper;

	private Datagram\SocketInterface|null $server = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly MetadataEntities\DevicesModule\Connector $connector,
		private readonly Consumers\Messages $consumer,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		$this->searchResult = new MdnsResultStorage();

		$this->parser = new Dns\Protocol\Parser();
		$this->dumper = new Dns\Protocol\BinaryDumper();
	}

	/**
	 * @throws BadMethodCallException
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		$factory = new Multicast\Factory($this->eventLoop);

		$server = $this->server = $factory->createReceiver(self::DNS_ADDRESS . ':' . self::DNS_PORT);

		$this->server->on('message', function ($message, $remote): void {
			try {
				$response = $this->parser->parseMessage($message);

			} catch (InvalidArgumentException) {
				$this->logger->warning(
					'Invalid DNS question response received',
					[
						'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
						'type' => 'mdns-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				return;
			}

			if ($response->tc) {
				$this->logger->warning(
					'The server set the truncated bit although we issued a TCP request',
					[
						'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
						'type' => 'mdns-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				return;
			}

			$serviceName = null;
			$serviceData = null;

			foreach ($response->answers as $answer) {
				if (
					$answer->type === Dns\Model\Message::TYPE_PTR
					&& is_string($answer->data)
					&& preg_match(self::MATCH_NAME, $answer->data) === 1
					&& $serviceName === null
				) {
					$serviceName = $answer->data;
				}

				if (
					$answer->type === Dns\Model\Message::TYPE_TXT
					&& preg_match(self::MATCH_NAME, $answer->name) === 1
					&& is_array($answer->data)
					&& $serviceData === null
				) {
					$serviceData = $answer->data;
				}
			}

			if ($serviceName !== null && $serviceData !== null) {
				preg_match('/^(\d[\d.]+):(\d+)\b/', $remote, $results);

				if (count($results) === 3) {
					$serviceResult = new MdnsResult($results[1], $serviceName, $serviceData);

					if (!$this->searchResult->contains($serviceResult)) {
						$this->searchResult->attach($serviceResult);

						preg_match(self::MATCH_NAME, $serviceName, $matches);

						$this->consumer->append(new Entities\Messages\DeviceFound(
							Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_MDNS),
							$this->connector->getId(),
							Utils\Strings::lower($matches['id']),
							Utils\Strings::lower($matches['devtype']),
							$results[1],
						));
					}
				}
			}
		});

		$this->eventLoop->futureTick(function () use ($server): void {
			$query = new Dns\Query\Query(
				'_http._tcp.local',
				Dns\Model\Message::TYPE_PTR,
				Dns\Model\Message::CLASS_IN,
			);

			$request = $this->dumper->toBinary(Dns\Model\Message::createRequestForQuery($query));

			$server->send($request, self::DNS_ADDRESS . ':' . self::DNS_PORT);
		});
	}

	public function disconnect(): void
	{
		$this->server?->close();
	}

}
