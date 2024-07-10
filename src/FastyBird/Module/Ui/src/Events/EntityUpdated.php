<?php declare(strict_types = 1);

/**
 * EntityUpdated.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           09.07.24
 */

namespace FastyBird\Module\Ui\Events;

use FastyBird\Module\Ui\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Doctrine entity was updated
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class EntityUpdated extends EventDispatcher\Event
{

	public function __construct(private readonly Entities\Entity $entity)
	{
	}

	public function getEntity(): Entities\Entity
	{
		return $this->entity;
	}

}
