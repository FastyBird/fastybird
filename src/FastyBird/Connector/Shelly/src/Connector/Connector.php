<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Connector
 * @since          1.0.0
 *
 * @date           14.07.22
 */

namespace FastyBird\Connector\Shelly\Connector;

use Evenement;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Writers;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Constants as DevicesConstants;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use React\EventLoop;
use React\Promise;
use ReflectionClass;
use function array_key_exists;
use function assert;
use function React\Async\async;

/**
 * Connector service executor
 *
 * @package        FastyBird:ShellyConnector!
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
	 * @param array<Writers\WriterFactory> $writersFactories
	 */
	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly array $clientsFactories,
		private readonly Clients\DiscoveryFactory $discoveryClientFactory,
		private readonly Helpers\Connector $connectorHelper,
		private readonly array $writersFactories,
		private readonly Queue\Queue $queue,
		private readonly Queue\Consumers $consumers,
		private readonly Shelly\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function execute(bool $standalone = true): Promise\PromiseInterface
	{
		assert($this->connector->getType() === Entities\ShellyConnector::TYPE);

		$this->logger->info(
			'Starting Shelly connector service',
			[
				'source' => MetadataTypes\ConnectorSource::SHELLY,
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
				!$this->client instanceof Clients\Local
				&& !$this->client instanceof Clients\Cloud
				&& !$this->client instanceof Clients\Mqtt
			)
		) {
			return Promise\reject(new Exceptions\InvalidState('Connector client is not configured'));
		}

		$this->client->connect();

		foreach ($this->writersFactories as $writerFactory) {
			if (
				(
					$standalone
					&& $writerFactory instanceof Writers\ExchangeFactory
				) || (
					!$standalone
					&& $writerFactory instanceof Writers\EventFactory
				)
			) {
				$this->writer = $writerFactory->create($this->connector);
				$this->writer->connect();
			}
		}

		$this->consumersTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumers->consume();
			}),
		);

		$this->logger->info(
			'Shelly connector service has been started',
			[
				'source' => MetadataTypes\ConnectorSource::SHELLY,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		return Promise\resolve(true);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function discover(): Promise\PromiseInterface
	{
		assert($this->connector->getType() === Entities\ShellyConnector::TYPE);

		$this->logger->info(
			'Starting Shelly connector discovery',
			[
				'source' => MetadataTypes\ConnectorSource::SHELLY,
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
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::FB_MQTT),
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
			'Shelly connector discovery has been started',
			[
				'source' => MetadataTypes\ConnectorSource::SHELLY,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->client->discover();

		return Promise\resolve(true);
	}

	public function terminate(): void
	{
		assert($this->connector->getType() === Entities\ShellyConnector::TYPE);

		$this->client?->disconnect();

		$this->writer?->disconnect();

		if ($this->consumersTimer !== null && $this->queue->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumersTimer);
		}

		$this->logger->info(
			'Shelly connector has been terminated',
			[
				'source' => MetadataTypes\ConnectorSource::SHELLY,
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
