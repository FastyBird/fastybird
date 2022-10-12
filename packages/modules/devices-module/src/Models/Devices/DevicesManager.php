<?php declare(strict_types = 1);

/**
 * DevicesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           28.07.18
 */

namespace FastyBird\DevicesModule\Models\Devices;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Models;
use IPub\DoctrineCrud\Crud;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Device entities manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicesManager
{

	use Nette\SmartObject;

	/**
	 * @param Crud\IEntityCrud<Entities\Devices\Device> $entityCrud
	 */
	public function __construct(private readonly Crud\IEntityCrud $entityCrud)
	{
		// Entity CRUD for handling entities
	}

	public function create(Utils\ArrayHash $values): Entities\Devices\Device
	{
		$entity = $this->entityCrud->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Devices\Device);

		return $entity;
	}

	public function update(
		Entities\Devices\Device $entity,
		Utils\ArrayHash $values,
	): Entities\Devices\Device
	{
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Devices\Device);

		return $entity;
	}

	public function delete(Entities\Devices\Device $entity): bool
	{
		// Delete entity from database
		return $this->entityCrud->getEntityDeleter()->delete($entity);
	}

}
