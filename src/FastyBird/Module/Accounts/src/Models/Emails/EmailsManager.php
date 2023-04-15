<?php declare(strict_types = 1);

/**
 * EmailsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Models\Emails;

use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Models;
use IPub\DoctrineCrud\Crud as DoctrineCrudCrud;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Accounts emails address entities manager
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EmailsManager
{

	use Nette\SmartObject;

	/**
	 * @param DoctrineCrudCrud\IEntityCrud<Entities\Emails\Email> $entityCrud
	 */
	public function __construct(private readonly DoctrineCrudCrud\IEntityCrud $entityCrud)
	{
		// Transformer CRUD for handling entities
	}

	public function create(Utils\ArrayHash $values): Entities\Emails\Email
	{
		$entity = $this->entityCrud->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Emails\Email);

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	public function update(
		Entities\Emails\Email $entity,
		Utils\ArrayHash $values,
	): Entities\Emails\Email
	{
		$entity = $this->entityCrud->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Emails\Email);

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	public function delete(Entities\Emails\Email $entity): bool
	{
		// Delete entity from database
		return $this->entityCrud->getEntityDeleter()->delete($entity);
	}

}
