<?php declare(strict_types = 1);

/**
 * ControlManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           01.10.21
 */

namespace FastyBird\Module\Triggers\Models\Triggers\Controls;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use IPub\DoctrineCrud\Crud as DoctrineCrudCrud;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Triggers controls entities manager
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ControlsManager
{

	use Nette\SmartObject;

	/**
	 * @param DoctrineCrudCrud\IEntityCrud<Entities\Triggers\Controls\Control> $entityCrud
	 */
	public function __construct(private readonly DoctrineCrudCrud\IEntityCrud $entityCrud)
	{
		// Transformer CRUD for handling entities
	}

	public function create(
		Utils\ArrayHash $values,
	): Entities\Triggers\Controls\Control
	{
		$entity = $this->entityCrud->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Triggers\Controls\Control);

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	public function update(
		Entities\Triggers\Controls\Control $entity,
		Utils\ArrayHash $values,
	): Entities\Triggers\Controls\Control
	{
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Triggers\Controls\Control);

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	public function delete(Entities\Triggers\Controls\Control $entity): bool
	{
		// Delete entity from database
		return $this->entityCrud->getEntityDeleter()->delete($entity);
	}

}
