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

use Evenement;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Writers;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Constants as DevicesConstants;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
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
 * @package        FastyBird:NsPanelConnector!
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

	private Servers\Server|null $server = null;

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
		private readonly Helpers\Entity $entityHelper,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Devices\ThirdPartyDevice $thirdPartyDeviceHelper,
		private readonly Queue\Queue $queue,
		private readonly Queue\Consumers $consumers,
		private readonly Servers\ServerFactory $serverFactory,
		private readonly array $writersFactories,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function execute(bool $standalone = true): Promise\PromiseInterface
	{
		assert($this->connector->getType() === Entities\NsPanelConnector::TYPE);

		$this->logger->info(
			'Starting NS Panel connector service',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL,
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
			$mode->equalsValue(NsPanel\Types\ClientMode::BOTH)
			|| $mode->equalsValue(NsPanel\Types\ClientMode::DEVICE)
		) {
			$this->server = $this->serverFactory->create($this->connector);
			$this->server->connect();
		}

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

		if (
			$mode->equalsValue(NsPanel\Types\ClientMode::BOTH)
			|| $mode->equalsValue(NsPanel\Types\ClientMode::DEVICE)
		) {
			$this->eventLoop->addTimer(1, async(function (): void {
				$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
				$findDevicesQuery->forConnector($this->connector);
				$findDevicesQuery->byType(Entities\Devices\ThirdPartyDevice::TYPE);

				$devices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

				foreach ($devices as $device) {
					$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
					$findChannelsQuery->forDevice($device);

					$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

					foreach ($channels as $channel) {
						try {
							$this->queue->append(
								$this->entityHelper->create(
									Entities\Messages\WriteThirdPartyDeviceState::class,
									[
										'connector' => $device->getConnector(),
										'device' => $device->getId(),
										'channel' => $channel->getId(),
									],
								),
							);
						} catch (Exceptions\Runtime $ex) {
							$this->logger->error(
								'Could not report device initial state to NS Panel',
								[
									'source' => MetadataTypes\Sources\Connector::NS_PANEL,
									'type' => 'connector',
									'exception' => ApplicationHelpers\Logger::buildException($ex),
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
									'gateway' => [
										'id' => $this->thirdPartyDeviceHelper->getGateway($device)->getId()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
									'channel' => [
										'id' => $channel->getId()->toString(),
									],
								],
							);
						}
					}
				}
			}));
		}

		$this->logger->info(
			'NS Panel connector service has been started',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL,
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function discover(): Promise\PromiseInterface
	{
		assert($this->connector->getType() === Entities\NsPanelConnector::TYPE);

		$this->logger->info(
			'Starting NS Panel connector discovery',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL,
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
						MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::FB_MQTT),
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
			'NS Panel connector discovery has been started',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL,
				'type' => 'connector',
				'connector' => [
					'id' => $this->connector->getId()->toString(),
				],
			],
		);

		$client->discover();

		return Promise\resolve(true);
	}

	public function terminate(): void
	{
		assert($this->connector->getType() === Entities\NsPanelConnector::TYPE);

		foreach ($this->clients as $client) {
			$client->disconnect();
		}

		$this->server?->disconnect();

		$this->writer?->disconnect();

		if ($this->consumersTimer !== null && $this->queue->isEmpty()) {
			$this->eventLoop->cancelTimer($this->consumersTimer);
		}

		$this->logger->info(
			'NS Panel connector has been terminated',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL,
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
