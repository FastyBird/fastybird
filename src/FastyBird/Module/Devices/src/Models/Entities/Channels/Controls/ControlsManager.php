<?php declare(strict_types = 1);

/**
 * ControlManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           09.06.19
 */

namespace FastyBird\Module\Devices\Models\Entities\Channels\Controls;

use Evenement;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use IPub\DoctrineCrud\Crud as DoctrineCrudCrud;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Channels controls entities manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ControlsManager extends Evenement\EventEmitter implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;

	/**
	 * @param DoctrineCrudCrud\IEntityCrud<Entities\Channels\Controls\Control> $entityCrud
	 */
	public function __construct(private readonly DoctrineCrudCrud\IEntityCrud $entityCrud)
	{
		// Transformer CRUD for handling entities
	}

	public function create(
		Utils\ArrayHash $values,
	): Entities\Channels\Controls\Control
	{
		$entity = $this->entityCrud->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Channels\Controls\Control);

		$this->emit(Devices\Constants::EVENT_ENTITY_CREATED, [$entity]);

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	public function update(
		Entities\Channels\Controls\Control $entity,
		Utils\ArrayHash $values,
	): Entities\Channels\Controls\Control
	{
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Channels\Controls\Control);

		$this->emit(Devices\Constants::EVENT_ENTITY_UPDATED, [$entity]);

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	public function delete(Entities\Channels\Controls\Control $entity): bool
	{
		// Delete entity from database
		$result = $this->entityCrud->getEntityDeleter()->delete($entity);

		if ($result) {
			$this->emit(Devices\Constants::EVENT_ENTITY_DELETED, [$entity]);
		}

		return $result;
	}

}
