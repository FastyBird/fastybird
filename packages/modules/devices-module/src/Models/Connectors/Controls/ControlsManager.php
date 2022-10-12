<?php declare(strict_types = 1);

/**
 * ControlManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.4.0
 *
 * @date           29.09.21
 */

namespace FastyBird\DevicesModule\Models\Connectors\Controls;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Models;
use IPub\DoctrineCrud\Crud;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Connectors controls entities manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ControlsManager
{

	use Nette\SmartObject;

	/**
	 * @param Crud\IEntityCrud<Entities\Connectors\Controls\Control> $entityCrud
	 */
	public function __construct(private readonly Crud\IEntityCrud $entityCrud)
	{
		// Entity CRUD for handling entities
	}

	public function create(
		Utils\ArrayHash $values,
	): Entities\Connectors\Controls\Control
	{
		$entity = $this->entityCrud->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Connectors\Controls\Control);

		return $entity;
	}

	public function update(
		Entities\Connectors\Controls\Control $entity,
		Utils\ArrayHash $values,
	): Entities\Connectors\Controls\Control
	{
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Connectors\Controls\Control);

		return $entity;
	}

	public function delete(Entities\Connectors\Controls\Control $entity): bool
	{
		// Delete entity from database
		return $this->entityCrud->getEntityDeleter()->delete($entity);
	}

}
