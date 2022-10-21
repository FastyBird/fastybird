<?php declare(strict_types = 1);

/**
 * KeysManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Models;

use FastyBird\Plugin\ApiKey\Entities;
use IPub\DoctrineCrud\Crud as DoctrineCrudCrud;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Key entities manager
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class KeysManager
{

	use Nette\SmartObject;

	/** @phpstan-var DoctrineCrudCrud\IEntityCrud<Entities\Key> */
	private DoctrineCrudCrud\IEntityCrud $entityCrud;

	/**
	 * @phpstan-param DoctrineCrudCrud\IEntityCrud<Entities\Key> $entityCrud
	 */
	public function __construct(DoctrineCrudCrud\IEntityCrud $entityCrud)
	{
		// Entity CRUD for handling entities
		$this->entityCrud = $entityCrud;
	}

	public function create(Utils\ArrayHash $values): Entities\Key
	{
		$entity = $this->entityCrud->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Key);

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	public function update(
		Entities\Key $entity,
		Utils\ArrayHash $values,
	): Entities\Key
	{
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Key);

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	public function delete(Entities\Key $entity): bool
	{
		// Delete entity from database
		return $this->entityCrud->getEntityDeleter()->delete($entity);
	}

}
