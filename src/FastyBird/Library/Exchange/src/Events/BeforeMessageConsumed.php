<?php declare(strict_types = 1);

/**
 * BeforeMessageConsumed.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Events
 * @since          0.45.0
 *
 * @date           19.06.22
 */

namespace FastyBird\Library\Exchange\Events;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Symfony\Contracts\EventDispatcher;

/**
 * Before message consumed event
 *
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class BeforeMessageConsumed extends EventDispatcher\Event
{

	public function __construct(
		private readonly MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\TriggerSource $source,
		private readonly MetadataTypes\RoutingKey $routingKey,
		private readonly MetadataEntities\Entity|null $entity,
	)
	{
	}

	public function getSource(): MetadataTypes\TriggerSource|MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource
	{
		return $this->source;
	}

	public function getRoutingKey(): MetadataTypes\RoutingKey
	{
		return $this->routingKey;
	}

	public function getEntity(): MetadataEntities\Entity|null
	{
		return $this->entity;
	}

}
