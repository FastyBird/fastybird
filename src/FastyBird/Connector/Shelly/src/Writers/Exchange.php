<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           14.12.22
 */

namespace FastyBird\Connector\Shelly\Writers;

use Exception;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use function assert;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange implements Writer, ExchangeConsumers\Consumer
{

	use Nette\SmartObject;

	public const NAME = 'exchange';

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly ExchangeConsumers\Container $consumer,
	)
	{
	}

	public function connect(): void
	{
		$this->consumer->enable(self::class);
	}

	public function disconnect(): void
	{
		$this->consumer->disable(self::class);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		if ($entity instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty) {
			$findChannelQuery = new DevicesQueries\Entities\FindChannels();
			$findChannelQuery->byId($entity->getChannel());

			$channel = $this->channelsRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				return;
			}

			$device = $channel->getDevice();
			assert($device instanceof Entities\ShellyDevice);

			if (!$device->getConnector()->getId()->equals($this->connector->getId())) {
				return;
			}

			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\WriteChannelPropertyState::class,
					[
						'connector' => $this->connector->getId()->toString(),
						'device' => $device->getId()->toString(),
						'channel' => $channel->getId()->toString(),
						'property' => $entity->getId()->toString(),
					],
				),
			);
		}
	}

}
