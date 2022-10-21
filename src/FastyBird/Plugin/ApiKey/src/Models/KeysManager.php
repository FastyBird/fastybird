<?php declare(strict_types = 1);

/**
 * KeysManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           16.05.21
 */

namespace FastyBird\MiniServer\Models\ApiKeys;

use FastyBird\MiniServer\Entities;
use IPub\DoctrineCrud\Crud;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Key entities manager
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class KeysManager implements IKeysManager
{

	use Nette\SmartObject;

	/** @phpstan-var Crud\IEntityCrud<Entities\ApiKeys\IKey> */
	private Crud\IEntityCrud $entityCrud;

	/**
	 * @phpstan-param Crud\IEntityCrud<Entities\ApiKeys\IKey> $entityCrud
	 */
	public function __construct(Crud\IEntityCrud $entityCrud)
	{
		// Entity CRUD for handling entities
		$this->entityCrud = $entityCrud;
	}

	public function create(Utils\ArrayHash $values): Entities\ApiKeys\IKey
	{
		$entity = $this->entityCrud->getEntityCreator()->create($values);
		assert($entity instanceof Entities\ApiKeys\IKey);

		return $entity;
	}

	public function update(
		Entities\ApiKeys\IKey $entity,
		Utils\ArrayHash $values,
	): Entities\ApiKeys\IKey
	{
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\ApiKeys\IKey);

		return $entity;
	}

	public function delete(Entities\ApiKeys\IKey $entity): bool
	{
		// Delete entity from database
		return $this->entityCrud->getEntityDeleter()->delete($entity);
	}

}
