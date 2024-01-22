<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Connector
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Connector;

use Evenement;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Writers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Constants as DevicesConstants;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use InvalidArgumentException;
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
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements DevicesConnectors\Connector
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const QUEUE_PROCESSING_INTERVAL = 0.01;

	/** @var array<Clients\Client|Clients\Discovery> */
	private array $clients = [];

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
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function execute(): Promise\PromiseInterface
	{
		assert($this->connector->getType() === Entities\Zigbee2MqttConnector::TYPE);

		$this->logger->info(
			'Starting Zigbee2MQTT connector service',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$mode = $this->connectorHelper->getClientMode($this->connector);

		$client = null;

		foreach ($this->clientsFactories as $clientFactory) {
			$rc = new ReflectionClass($clientFactory);

			$constants = $rc->getConstants();

			if (
				array_key_exists(Clients\ClientFactory::MODE_CONSTANT_NAME, $constants)
				&& $mode->equalsValue($constants[Clients\ClientFactory::MODE_CONSTANT_NAME])
			) {
				$client = $clientFactory->create($this->connector);

				$this->clients[] = $client;
			}
		}

		if (!$client instanceof Clients\Mqtt) {
			return Promise\reject(new Exceptions\InvalidState('Connector client is not configured'));
		}

		$client->connect();

		$this->writer = $this->writerFactory->create($this->connector);
		$this->writer->connect();

		$this->consumersTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumers->consume();
			}),
		);

		$this->logger->info(
			'Zigbee2MQTT connector service has been started',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
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
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function discover(): Promise\PromiseInterface
	{
		assert($this->connector->getType() === Entities\Zigbee2MqttConnector::TYPE);

		$this->logger->info(
			'Starting Zigbee2MQTT connector discovery',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$client = $this->discoveryClientFactory->create($this->connector);

		$client->on('finished', function (): void {
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

		$this->clients[] = $client;

		$this->consumersTimer = $this->eventLoop->addPeriodicTimer(
			self::QUEUE_PROCESSING_INTERVAL,
			async(function (): void {
				$this->consumers->consume();
			}),
		);

		$this->logger->info(
			'Zigbee2MQTT connector discovery has been started',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$client->discover();

		return Promise\resolve(true);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function terminate(): void
	{
		assert($this->connector->getType() === Entities\Zigbee2MqttConnector::TYPE);

		foreach ($this->clients as $client) {
			$client->disconnect();
		}

		$this->writer?->disconnect();

		if ($this->consumersTimer !== null && $this->queue->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumersTimer);
		}

		$this->logger->info(
			'Zigbee2MQTT connector has been terminated',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
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
