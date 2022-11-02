<?php declare(strict_types = 1);

/**
 * ControlManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          0.4.0
 *
 * @date           01.10.21
 */

namespace FastyBird\TriggersModule\Models\Triggers\Controls;

use FastyBird\TriggersModule\Entities;
use FastyBird\TriggersModule\Models;
use IPub\DoctrineCrud\Crud;
use Nette;
use Nette\Utils;

/**
 * Triggers controls entities manager
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ControlsManager implements IControlsManager
{

	use Nette\SmartObject;

	/**
	 * @var Crud\IEntityCrud
	 *
	 * @phpstan-var Crud\IEntityCrud<Entities\Triggers\Controls\IControl>
	 */
	private Crud\IEntityCrud $entityCrud;

	/**
	 * @phpstan-param Crud\IEntityCrud<Entities\Triggers\Controls\IControl> $entityCrud
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
	): Entities\Triggers\Controls\IControl {
		/** @var Entities\Triggers\Controls\IControl $entity */
		$entity = $this->entityCrud->getEntityCreator()->create($values);

		return $entity;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update(
		Entities\Triggers\Controls\IControl $entity,
		Utils\ArrayHash $values
	): Entities\Triggers\Controls\IControl {
		/** @var Entities\Triggers\Controls\IControl $entity */
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);

		return $entity;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(
		Entities\Triggers\Controls\IControl $entity
	): bool {
		// Delete entity from database
		return $this->entityCrud->getEntityDeleter()->delete($entity);
	}

}
