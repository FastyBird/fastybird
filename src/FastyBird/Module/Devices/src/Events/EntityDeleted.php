<?php declare(strict_types = 1);

/**
 * EntityDeleted.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          0.65.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Events;

use FastyBird\Module\Devices\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Module entity was deleted event
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class EntityDeleted extends EventDispatcher\Event
{

	public function __construct(private readonly Entities\Entity $entity)
	{
	}

	public function getEntity(): Entities\Entity
	{
		return $this->entity;
	}

}
