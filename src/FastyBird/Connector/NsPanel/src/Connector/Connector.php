<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Connector
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Connector;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Consumers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Nette;
use React\EventLoop;
use function assert;
use function React\Async\async;

/**
 * Connector service executor
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements DevicesConnectors\Connector
{

	use Nette\SmartObject;

	private const QUEUE_PROCESSING_INTERVAL = 0.01;

	/** @var array<Clients\Client> */
	private array $clients = [];

	private EventLoop\TimerInterface|null $consumerTimer = null;

	/**
	 * @param array<Clients\ClientFactory> $clientsFactories
	 */
	public function __construct(
		private readonly DevicesEntities\Connectors\Connector $connector,
		private readonly array $clientsFactories,
		private readonly NsPanel\Logger $logger,
		private readonly Consumers\Messages $consumer,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	public function execute(): void
	{
		assert($this->connector instanceof Entities\NsPanelConnector);

		$this->logger->debug(
			'Starting NS Panel connector processing',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);

		foreach ($this->clientsFactories as $clientFactory) {
			$client = $clientFactory->create($this->connector);
			$client->connect();

			$this->clients[] = $client;
		}

		$this->consumerTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumer->consume();
			}),
		);

		$this->logger->debug(
			'Connector has been started',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);
	}

	public function discover(): void
	{
		assert($this->connector instanceof Entities\NsPanelConnector);

		$this->logger->debug(
			'Starting NS Panel connector discovery',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);

		// TODO: Implement discovery

		$this->consumerTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumer->consume();
			}),
		);

		$this->logger->debug(
			'Connector discovery has been started',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);
	}

	public function terminate(): void
	{
		foreach ($this->clients as $client) {
			$client->disconnect();
		}

		if ($this->consumerTimer !== null) {
			$this->eventLoop->cancelTimer($this->consumerTimer);
		}

		$this->logger->debug(
			'Connector has been terminated',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);
	}

	public function hasUnfinishedTasks(): bool
	{
		return !$this->consumer->isEmpty() && $this->consumerTimer !== null;
	}

}
