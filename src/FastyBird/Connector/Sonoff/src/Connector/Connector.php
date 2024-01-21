<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Connector
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\Connector;

use BadMethodCallException;
use Evenement;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Clients;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Writers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Constants as DevicesConstants;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use InvalidArgumentException;
use Nette;
use React\EventLoop;
use React\Promise;
use ReflectionClass;
use RuntimeException;
use function array_key_exists;
use function assert;
use function React\Async\async;

/**
 * Connector service executor
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements DevicesConnectors\Connector
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const QUEUE_PROCESSING_INTERVAL = 0.01;

	private Clients\Client|Clients\Discovery|null $client = null;

	private Writers\Writer|null $writer = null;

	private EventLoop\TimerInterface|null $consumersTimer = null;

	/**
	 * @param array<Clients\ClientFactory> $clientsFactories
	 */
	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly array $clientsFactories,
		private readonly Clients\DiscoveryFactory $discoveryClientFactory,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Writers\WriterFactory $writerFactory,
		private readonly Queue\Queue $queue,
		private readonly Queue\Consumers $consumers,
		private readonly Sonoff\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws BadMethodCallException
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\CloudApiCall
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	public function execute(): void
	{
		assert($this->connector->getType() === Entities\SonoffConnector::TYPE);

		$this->logger->info(
			'Starting Sonoff connector service',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_SONOFF,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$mode = $this->connectorHelper->getClientMode($this->connector);

		foreach ($this->clientsFactories as $clientFactory) {
			$rc = new ReflectionClass($clientFactory);

			$constants = $rc->getConstants();

			if (
				array_key_exists(Clients\ClientFactory::MODE_CONSTANT_NAME, $constants)
				&& $mode->equalsValue($constants[Clients\ClientFactory::MODE_CONSTANT_NAME])
			) {
				$this->client = $clientFactory->create($this->connector);
			}
		}

		if (
			$this->client === null
			|| (
				!$this->client instanceof Clients\Lan
				&& !$this->client instanceof Clients\Cloud
				&& !$this->client instanceof Clients\Auto
			)
		) {
			$this->emit(
				DevicesConstants::EVENT_TERMINATE,
				[
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::CONNECTOR_FB_MQTT),
						'Connector client is not configured',
					),
				],
			);

			return;
		}

		$this->client->connect();

		$this->writer = $this->writerFactory->create($this->connector);
		$this->writer->connect();

		$this->consumersTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumers->consume();
			}),
		);

		$this->logger->info(
			'Sonoff connector service has been started',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_SONOFF,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	public function discover(): Promise\PromiseInterface
	{
		assert($this->connector->getType() === Entities\SonoffConnector::TYPE);

		$this->logger->info(
			'Starting Sonoff connector discovery',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_SONOFF,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->client = $this->discoveryClientFactory->create($this->connector);

		$this->client->on('finished', function (): void {
			$this->emit(
				DevicesConstants::EVENT_TERMINATE,
				[
					new DevicesEvents\TerminateConnector(
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::CONNECTOR_FB_MQTT),
						'Devices discovery finished',
					),
				],
			);
		});

		$this->consumersTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumers->consume();
			}),
		);

		$this->logger->info(
			'Sonoff connector discovery has been started',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_SONOFF,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->client->discover();

		return Promise\resolve(true);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function terminate(): void
	{
		assert($this->connector->getType() === Entities\SonoffConnector::TYPE);

		$this->client?->disconnect();

		$this->writer?->disconnect();

		if ($this->consumersTimer !== null && $this->queue->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumersTimer);
		}

		$this->logger->info(
			'Sonoff connector has been terminated',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_SONOFF,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	public function hasUnfinishedTasks(): bool
	{
		return !$this->queue->isEmpty() && $this->consumersTimer !== null;
	}

}
