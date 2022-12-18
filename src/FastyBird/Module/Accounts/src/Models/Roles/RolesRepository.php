<?php declare(strict_types = 1);

/**
 * RolesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Models\Roles;

use Doctrine\ORM;
use Doctrine\Persistence;
use Exception;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Queries;
use IPub\DoctrineOrmQuery;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
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

	/** @phpstan-var ORM\EntityRepository<Entities\Roles\Role>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findOneByName(string $keyName): Entities\Roles\Role|null
	{
		$findQuery = new Queries\FindRoles();
		$findQuery->byName($keyName);

		return $this->findOneBy($findQuery);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findOneBy(
		Queries\FindRoles $queryObject,
	): Entities\Roles\Role|null
	{
		return $queryObject->fetchOne($this->getRepository());
	}

	/**
	 * @phpstan-return array<Entities\Roles\Role>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findAllBy(Queries\FindRoles $queryObject): array
	{
		/** @var array<Entities\Roles\Role>|DoctrineOrmQuery\ResultSet<Entities\Roles\Role> $result */
		$result = $queryObject->fetch($this->getRepository());

		if (is_array($result)) {
			return $result;
		}

		/** @var array<Entities\Roles\Role> $data */
		$data = $result->toArray();

		return $data;
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Roles\Role>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function getResultSet(
		Queries\FindRoles $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		/** @var DoctrineOrmQuery\ResultSet<Entities\Roles\Role> $result */
		$result = $queryObject->fetch($this->getRepository());

		return $result;
	}

	/**
	 * @phpstan-param class-string<Entities\Roles\Role> $type
	 *
	 * @phpstan-return ORM\EntityRepository<Entities\Roles\Role>
	 */
	private function getRepository(string $type = Entities\Roles\Role::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
