<?php declare(strict_types = 1);

/**
 * ITriggersManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\TriggersModule\Models\Triggers;

use FastyBird\TriggersModule\Entities;
use IPub\DoctrineCrud\Crud;
use Nette;
use Nette\Utils;

/**
 * Triggers entities manager
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TriggersManager implements ITriggersManager
{

	use Nette\SmartObject;

	/**
	 * @var Crud\IEntityCrud
	 *
	 * @phpstan-var Crud\IEntityCrud<Entities\Triggers\ITrigger>
	 */
	private Crud\IEntityCrud $entityCrud;

	/**
	 * @phpstan-param Crud\IEntityCrud<Entities\Triggers\ITrigger> $entityCrud
	 */
	public function __construct(
		Crud\IEntityCrud $entityCrud
	) {
		// Entity CRUD for handling entities
		$this->entityCrud = $entityCrud;
	}

	/**
	 * {@inheritDoc}
	 */
	public function create(
		Utils\ArrayHash $values
	): Entities\Triggers\ITrigger {
		/** @var Entities\Triggers\ITrigger $entity */
		$entity = $this->entityCrud->getEntityCreator()->create($values);

		return $entity;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update(
		Entities\Triggers\ITrigger $entity,
		Utils\ArrayHash $values
	): Entities\Triggers\ITrigger {
		/** @var Entities\Triggers\ITrigger $entity */
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);

		return $entity;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(
		Entities\Triggers\ITrigger $entity
	): bool {
		// Delete entity from database
		return $this->entityCrud->getEntityDeleter()->delete($entity);
	}

}
