<?php declare(strict_types = 1);

/**
 * RolesRepository.php
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

namespace FastyBird\Module\Accounts\Models\Entities\Roles;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Utilities;
use IPub\DoctrineOrmQuery;
use Nette;
use Throwable;
use function is_array;

/**
 * ACL role repository
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class RolesRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Roles\Role>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(
		private readonly Utilities\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function findOneByName(string $keyName): Entities\Roles\Role|null
	{
		$findQuery = new Queries\Entities\FindRoles();
		$findQuery->byName($keyName);

		return $this->findOneBy($findQuery);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindRoles $queryObject,
	): Entities\Roles\Role|null
	{
		return $this->database->query(
			fn (): Entities\Roles\Role|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @return array<Entities\Roles\Role>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(Queries\Entities\FindRoles $queryObject): array
	{
		try {
			/** @var array<Entities\Roles\Role> $result */
			$result = $this->getResultSet($queryObject)->toArray();

			return $result;
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Fetch all data by query failed', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return DoctrineOrmQuery\ResultSet<Entities\Roles\Role>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindRoles $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		$result = $this->database->query(
			fn (): DoctrineOrmQuery\ResultSet|array => $queryObject->fetch($this->getRepository()),
		);

		if (is_array($result)) {
			throw new Exceptions\InvalidState('Result set could not be created');
		}

		return $result;
	}

	/**
	 * @param class-string<Entities\Roles\Role> $type
	 *
	 * @return ORM\EntityRepository<Entities\Roles\Role>
	 */
	private function getRepository(string $type = Entities\Roles\Role::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
