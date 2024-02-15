<?php declare(strict_types = 1);

/**
 * ConnectorsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           16.04.21
 */

namespace FastyBird\Module\Devices\Models\Entities\Connectors;

use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Models;
use IPub\DoctrineCrud\Crud as DoctrineCrudCrud;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher;
use function assert;

/**
 * Connectors entities manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorsManager
{

	use Nette\SmartObject;

	/**
	 * @param DoctrineCrudCrud\IEntityCrud<Entities\Connectors\Connector> $entityCrud
	 */
	public function __construct(
		private readonly DoctrineCrudCrud\IEntityCrud $entityCrud,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		// Transformer CRUD for handling entities
	}

	public function create(
		Utils\ArrayHash $values,
	): Entities\Connectors\Connector
	{
		$entity = $this->entityCrud->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Connectors\Connector);

		$this->dispatcher?->dispatch(new Events\EntityCreated($entity));

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	public function update(
		Entities\Connectors\Connector $entity,
		Utils\ArrayHash $values,
	): Entities\Connectors\Connector
	{
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Connectors\Connector);

		$this->dispatcher?->dispatch(new Events\EntityUpdated($entity));

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	public function delete(Entities\Connectors\Connector $entity): bool
	{
		// Delete entity from database
		$result = $this->entityCrud->getEntityDeleter()->delete($entity);

		if ($result) {
			$this->dispatcher?->dispatch(new Events\EntityDeleted($entity));
		}

		return $result;
	}

}
