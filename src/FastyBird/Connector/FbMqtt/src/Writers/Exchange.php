<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           18.01.23
 */

namespace FastyBird\Connector\FbMqtt\Writers;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use React\EventLoop;
use function array_merge;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange extends Periodic implements Writer, ExchangeConsumers\Consumer
{

	public const NAME = 'exchange';

	/**
	 * @throws ExchangeExceptions\InvalidArgument
	 */
	public function __construct(
		MetadataDocuments\DevicesModule\Connector $connector,
		Helpers\Entity $entityHelper,
		Queue\Queue $queue,
		DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $eventLoop,
		private readonly ExchangeConsumers\Container $consumer,
	)
	{
		parent::__construct(
			$connector,
			$entityHelper,
			$queue,
			$devicesConfigurationRepository,
			$channelsConfigurationRepository,
			$devicesPropertiesConfigurationRepository,
			$channelsPropertiesConfigurationRepository,
			$devicePropertiesStatesManager,
			$channelPropertiesStatesManager,
			$dateTimeFactory,
			$eventLoop,
		);

		$this->consumer->register($this, null, false);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 */
	public function connect(): void
	{
		parent::connect();

		$this->consumer->enable(self::class);
	}

	/**
	 * @throws ExchangeExceptions\InvalidArgument
	 */
	public function disconnect(): void
	{
		parent::disconnect();

		$this->consumer->disable(self::class);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataDocuments\Document|null $entity,
	): void
	{
		if ($entity instanceof MetadataDocuments\DevicesModule\DevicePropertyState) {
			if (
				$entity->getGet()->getExpectedValue() === null
				|| $entity->getPending() !== true
			) {
				return;
			}

			$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
			$findDeviceQuery->forConnector($this->connector);
			$findDeviceQuery->byId($entity->getDevice());
			$findDeviceQuery->byType(Entities\FbMqttDevice::TYPE);

			$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

			if ($device === null) {
				return;
			}

			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\WriteDevicePropertyState::class,
					[
						'connector' => $this->connector->getId(),
						'device' => $device->getId(),
						'property' => $entity->getId(),
						'state' => array_merge(
							$entity->getGet()->toArray(),
							[
								'id' => $entity->getId(),
								'valid' => $entity->isValid(),
								'pending' => $entity->getPending(),
							],
						),
					],
				),
			);

		} elseif ($entity instanceof MetadataDocuments\DevicesModule\ChannelPropertyState) {
			if (
				$entity->getGet()->getExpectedValue() === null
				|| $entity->getPending() !== true
			) {
				return;
			}

			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->byId($entity->getChannel());
			$findChannelQuery->byType(Entities\FbMqttChannel::TYPE);

			$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				return;
			}

			$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
			$findDeviceQuery->forConnector($this->connector);
			$findDeviceQuery->byId($channel->getDevice());
			$findDeviceQuery->byType(Entities\FbMqttDevice::TYPE);

			$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

			if ($device === null) {
				return;
			}

			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\WriteChannelPropertyState::class,
					[
						'connector' => $this->connector->getId(),
						'device' => $device->getId(),
						'channel' => $channel->getId(),
						'property' => $entity->getId(),
						'state' => array_merge(
							$entity->getGet()->toArray(),
							[
								'id' => $entity->getId(),
								'valid' => $entity->isValid(),
								'pending' => $entity->getPending(),
							],
						),
					],
				),
			);
		}
	}

}
