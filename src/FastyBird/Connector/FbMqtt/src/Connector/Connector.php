<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Connector
 * @since          1.0.0
 *
 * @date           23.07.22
 */

namespace FastyBird\Connector\FbMqtt\Connector;

use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Clients;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Writers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use InvalidArgumentException;
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
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements DevicesConnectors\Connector
{

	use Nette\SmartObject;

	private const QUEUE_PROCESSING_INTERVAL = 0.01;

	private FbMqtt\Clients\Client|null $client = null;

	private Writers\Writer|null $writer = null;

	private EventLoop\TimerInterface|null $consumersTimer = null;

	/**
	 * @param array<Clients\ClientFactory> $clientsFactories
	 */
	public function __construct(
		private readonly DevicesEntities\Connectors\Connector $connector,
		private readonly array $clientsFactories,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Writers\WriterFactory $writerFactory,
		private readonly Queue\Queue $queue,
		private readonly Queue\Consumers $consumers,
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function execute(): void
	{
		assert($this->connector instanceof Entities\FbMqttConnector);

		$this->logger->info(
			'Starting FB MQTT connector service',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$findConnector = new DevicesQueries\Configuration\FindConnectors();
		$findConnector->byId($this->connector->getId());
		$findConnector->byType(Entities\FbMqttConnector::TYPE);

		$connector = $this->connectorsConfigurationRepository->findOneBy($findConnector);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'connector',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		$version = $this->connectorHelper->getProtocolVersion($connector);

		foreach ($this->clientsFactories as $clientFactory) {
			$rc = new ReflectionClass($clientFactory);

			$constants = $rc->getConstants();

			if (
				array_key_exists(Clients\ClientFactory::VERSION_CONSTANT_NAME, $constants)
				&& $version->equalsValue($constants[Clients\ClientFactory::VERSION_CONSTANT_NAME])
			) {
				$this->client = $clientFactory->create($connector);
			}
		}

		if ($this->client === null) {
			$this->dispatcher?->dispatch(
				new DevicesEvents\TerminateConnector(
					MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT),
					'Connector protocol version is not configured',
				),
			);

			return;
		}

		$this->client->connect();

		$this->writer = $this->writerFactory->create($connector);
		$this->writer->connect();

		$this->consumersTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumers->consume();
			}),
		);

		$this->logger->info(
			'FB MQTT connector service has been started',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	public function discover(): void
	{
		assert($this->connector instanceof Entities\FbMqttConnector);

		$this->logger->error(
			'Devices discovery is not allowed for FB MQTT connector type',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	public function terminate(): void
	{
		$this->client?->disconnect();

		$this->writer?->disconnect();

		if ($this->consumersTimer !== null && $this->queue->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumersTimer);
		}

		$this->logger->info(
			'FB MQTT connector has been terminated',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
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
