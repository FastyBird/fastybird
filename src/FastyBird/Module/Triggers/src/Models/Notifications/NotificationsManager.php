<?php declare(strict_types = 1);

/**
 * NotificationsManager.php
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

namespace FastyBird\TriggersModule\Models\Notifications;

use FastyBird\TriggersModule\Entities;
use IPub\DoctrineCrud\Crud;
use Nette;
use Nette\Utils;

/**
 * Notifications entities manager
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NotificationsManager implements INotificationsManager
{

	use Nette\SmartObject;

	/**
	 * @var Crud\IEntityCrud
	 *
	 * @phpstan-var Crud\IEntityCrud<Entities\Notifications\INotification>
	 */
	private Crud\IEntityCrud $entityCrud;

	/**
	 * @phpstan-param Crud\IEntityCrud<Entities\Notifications\INotification> $entityCrud
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
	): Entities\Notifications\INotification {
		/** @var Entities\Notifications\INotification $entity */
		$entity = $this->entityCrud->getEntityCreator()->create($values);

		return $entity;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update(
		Entities\Notifications\INotification $entity,
		Utils\ArrayHash $values
	): Entities\Notifications\INotification {
		/** @var Entities\Notifications\INotification $entity */
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);

		return $entity;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(
		Entities\Notifications\INotification $entity
	): bool {
		// Delete entity from database
		return $this->entityCrud->getEntityDeleter()->delete($entity);
	}

}
