<?php declare(strict_types = 1);

/**
 * BeforeMessageConsumed.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Exchange!
 * @subpackage     Events
 * @since          0.45.0
 *
 * @date           19.06.22
 */

namespace FastyBird\Exchange\Events;

use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Types as MetadataTypes;
use Symfony\Contracts\EventDispatcher;

/**
 * Before message consumed event
 *
 * @package        FastyBird:Exchange!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class BeforeMessageConsumed extends EventDispatcher\Event
{

	public function __construct(
		private readonly MetadataTypes\RoutingKey $routingKey,
		private readonly MetadataEntities\Entity|null $entity,
	)
	{
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
