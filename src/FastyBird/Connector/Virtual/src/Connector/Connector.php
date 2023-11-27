<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Connector
 * @since          1.0.0
 *
 * @date           18.10.23
 */

namespace FastyBird\Connector\Virtual\Connector;

use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Devices;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Connector\Virtual\Writers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use React\EventLoop;
use function assert;
use function React\Async\async;

/**
 * Connector service executor
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements DevicesConnectors\Connector
{

	use Nette\SmartObject;

	private const QUEUE_PROCESSING_INTERVAL = 0.01;

	private Devices\Devices|null $devices = null;

	private Writers\Writer|null $writer = null;

	private EventLoop\TimerInterface|null $consumersTimer = null;

	public function __construct(
		private readonly DevicesEntities\Connectors\Connector $connector,
		private readonly Devices\DevicesFactory $devicesFactory,
		private readonly Writers\WriterFactory $writerFactory,
		private readonly Queue\Queue $queue,
		private readonly Queue\Consumers $consumers,
		private readonly Virtual\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function execute(): void
	{
		assert($this->connector instanceof Entities\VirtualConnector);

		$this->logger->info(
			'Starting Virtual connector service',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$findConnector = new DevicesQueries\Configuration\FindConnectors();
		$findConnector->byId($this->connector->getId());

		$connector = $this->connectorsConfigurationRepository->findOneBy($findConnector);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'connector',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		$this->devices = $this->devicesFactory->create($connector);

		$this->writer = $this->writerFactory->create($connector);
		$this->writer->connect();

		$this->consumersTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumers->consume();
			}),
		);

		$this->devices->start();

		$this->logger->info(
			'Virtual connector service has been started',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	public function discover(): void
	{
		assert($this->connector instanceof Entities\VirtualConnector);

		$this->logger->error(
			'Devices discovery is not allowed for Virtual connector type',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	public function terminate(): void
	{
		$this->devices?->stop();

		$this->writer?->disconnect();

		if ($this->consumersTimer !== null && $this->queue->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumersTimer);
		}

		$this->logger->info(
			'Virtual connector has been terminated',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
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
