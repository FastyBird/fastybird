<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Writers
 * @since          0.13.0
 *
 * @date           14.12.22
 */

namespace FastyBird\Connector\Tuya\Writers;

use FastyBird\Connector\Tuya\Clients;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use function assert;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange implements Writer, ExchangeConsumers\Consumer
{

	use Nette\SmartObject;

	public const NAME = 'exchange';

	private Entities\TuyaConnector|null $connector = null;

	private Clients\Client|null $client = null;

	public function __construct(
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $propertiesRepository,
		private readonly ExchangeConsumers\Container $consumer,
	)
	{
	}

	public function connect(
		Entities\TuyaConnector $connector,
		Clients\Client $client,
	): void
	{
		$this->connector = $connector;
		$this->client = $client;

		$this->consumer->enable(self::class);
	}

	public function disconnect(): void
	{
		$this->consumer->disable(self::class);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		if ($entity instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty) {
			$findPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findPropertyQuery->byId($entity->getId());

			$property = $this->propertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				return;
			}

			assert($property instanceof DevicesEntities\Channels\Properties\Dynamic);

			if (!$property->getChannel()->getDevice()->getConnector()->getId()->equals($this->connector?->getId())) {
				return;
			}

			$device = $property->getChannel()->getDevice();
			$channel = $property->getChannel();

			assert($device instanceof Entities\TuyaDevice);

			$this->client?->writeChannelProperty($device, $channel, $property);
		}
	}

}
