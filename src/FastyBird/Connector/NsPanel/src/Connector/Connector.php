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
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\EventLoop;
use ReflectionClass;
use function array_key_exists;
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

	/** @var array<Clients\Client|Clients\Discovery> */
	private array $clients = [];

	private Servers\Server|null $server = null;

	private EventLoop\TimerInterface|null $consumerTimer = null;

	/**
	 * @param array<Clients\ClientFactory> $clientsFactories
	 */
	public function __construct(
		private readonly DevicesEntities\Connectors\Connector $connector,
		private readonly array $clientsFactories,
		private readonly Clients\DiscoveryFactory $discoveryClientFactory,
		private readonly Servers\ServerFactory $serverFactory,
		private readonly NsPanel\Logger $logger,
		private readonly Consumers\Messages $consumer,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function execute(): void
	{
		assert($this->connector instanceof Entities\NsPanelConnector);

		$mode = $this->connector->getClientMode();

		$this->logger->debug(
			'Starting NS Panel connector service',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		foreach ($this->clientsFactories as $clientFactory) {
			$rc = new ReflectionClass($clientFactory);

			$constants = $rc->getConstants();

			if (
				(
					array_key_exists(Clients\ClientFactory::MODE_CONSTANT_NAME, $constants)
					&& $mode->equalsValue($constants[Clients\ClientFactory::MODE_CONSTANT_NAME])
				) || $mode->equalsValue(NsPanel\Types\ClientMode::BOTH)
			) {
				$client = $clientFactory->create($this->connector);
				$client->connect();

				$this->clients[] = $client;
			}
		}

		if (
			$this->connector->getClientMode()->equalsValue(NsPanel\Types\ClientMode::BOTH)
			|| $this->connector->getClientMode()->equalsValue(NsPanel\Types\ClientMode::DEVICE)
		) {
			$this->server = $this->serverFactory->create($this->connector);
			$this->server->connect();
		}

		$this->consumerTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumer->consume();
			}),
		);

		$this->logger->debug(
			'NS Panel connector service has been started',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function discover(): void
	{
		assert($this->connector instanceof Entities\NsPanelConnector);

		$this->logger->debug(
			'Starting NS Panel connector discovery',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->server?->disconnect();

		$client = $this->discoveryClientFactory->create($this->connector);

		$client->on('finished', function (): void {
			$this->dispatcher?->dispatch(
				new DevicesEvents\TerminateConnector(
					MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL),
					'Devices discovery finished',
				),
			);
		});

		$this->clients[] = $client;

		$this->consumerTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumer->consume();
			}),
		);

		$this->logger->debug(
			'NS Panel connector discovery has been started',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$client->discover();
	}

	public function terminate(): void
	{
		foreach ($this->clients as $client) {
			$client->disconnect();
		}

		if ($this->consumerTimer !== null && $this->consumer->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumerTimer);
		}

		$this->logger->debug(
			'NS Panel connector has been terminated',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	public function hasUnfinishedTasks(): bool
	{
		return !$this->consumer->isEmpty() && $this->consumerTimer !== null;
	}

}
