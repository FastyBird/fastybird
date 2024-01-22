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

use Evenement;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Devices;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Connector\Virtual\Writers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use React\EventLoop;
use React\Promise;
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
	use Evenement\EventEmitterTrait;

	private const QUEUE_PROCESSING_INTERVAL = 0.01;

	private Devices\Devices|null $devices = null;

	private Writers\Writer|null $writer = null;

	private EventLoop\TimerInterface|null $consumersTimer = null;

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly Devices\DevicesFactory $devicesFactory,
		private readonly Writers\WriterFactory $writerFactory,
		private readonly Queue\Queue $queue,
		private readonly Queue\Consumers $consumers,
		private readonly Virtual\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	public function execute(): Promise\PromiseInterface
	{
		assert($this->connector->getType() === Entities\VirtualConnector::TYPE);

		$this->logger->info(
			'Starting Virtual connector service',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$this->devices = $this->devicesFactory->create($this->connector);

		$this->writer = $this->writerFactory->create($this->connector);
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
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
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
	 */
	public function discover(): Promise\PromiseInterface
	{
		return Promise\reject(
			new Exceptions\InvalidState('Devices discovery is not allowed for Virtual connector type'),
		);
	}

	public function terminate(): void
	{
		assert($this->connector->getType() === Entities\VirtualConnector::TYPE);

		$this->devices?->stop();

		$this->writer?->disconnect();

		if ($this->consumersTimer !== null && $this->queue->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumersTimer);
		}

		$this->logger->info(
			'Virtual connector has been terminated',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_VIRTUAL,
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
