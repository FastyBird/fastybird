<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Connector
 * @since          1.0.0
 *
 * @date           31.07.22
 */

namespace FastyBird\Connector\Modbus\Connector;

use FastyBird\Connector\Modbus;
use FastyBird\Connector\Modbus\Clients;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Helpers;
use FastyBird\Connector\Modbus\Queue;
use FastyBird\Connector\Modbus\Writers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
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
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements DevicesConnectors\Connector
{

	use Nette\SmartObject;

	private const QUEUE_PROCESSING_INTERVAL = 0.01;

	private Clients\Client|null $client = null;

	private Writers\Writer|null $writer = null;

	private EventLoop\TimerInterface|null $consumersTimer = null;

	/**
	 * @param array<Clients\ClientFactory> $clientsFactories
	 */
	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly array $clientsFactories,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Writers\WriterFactory $writerFactory,
		private readonly Queue\Queue $queue,
		private readonly Queue\Consumers $consumers,
		private readonly Modbus\Logger $logger,
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
		assert($this->connector->getType() === Entities\ModbusConnector::TYPE);

		$this->logger->info(
			'Starting Modbus connector service',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_MODBUS,
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

		if ($this->client === null) {
			$this->dispatcher?->dispatch(
				new DevicesEvents\TerminateConnector(
					MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::CONNECTOR_MODBUS),
					'Connector client is not configured',
				),
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
			'Modbus connector service has been started',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_MODBUS,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	public function discover(): void
	{
		assert($this->connector->getType() === Entities\ModbusConnector::TYPE);

		$this->logger->error(
			'Devices discovery is not allowed for Modbus connector type',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_MODBUS,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);
	}

	public function terminate(): void
	{
		assert($this->connector->getType() === Entities\ModbusConnector::TYPE);

		$this->client?->disconnect();

		$this->writer?->disconnect();

		if ($this->consumersTimer !== null && $this->queue->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumersTimer);
		}

		$this->logger->info(
			'Modbus connector has been terminated',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_MODBUS,
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
