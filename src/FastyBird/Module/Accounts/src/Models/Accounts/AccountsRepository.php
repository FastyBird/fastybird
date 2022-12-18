<?php declare(strict_types = 1);

/**
 * AccountsRepository.php
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

namespace FastyBird\Module\Accounts\Models\Accounts;

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
 * Account repository
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AccountsRepository
{

	use Nette\SmartObject;

	/** @phpstan-var ORM\EntityRepository<Entities\Accounts\Account>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findOneBy(
		Queries\FindAccounts $queryObject,
	): Entities\Accounts\Account|null
	{
		return $queryObject->fetchOne($this->getRepository());
	}

	/**
	 * @phpstan-return array<Entities\Accounts\Account>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findAllBy(Queries\FindAccounts $queryObject): array
	{
		/** @var array<Entities\Accounts\Account>|DoctrineOrmQuery\ResultSet<Entities\Accounts\Account> $result */
		$result = $queryObject->fetch($this->getRepository());

		if (is_array($result)) {
			return $result;
		}

		/** @var array<Entities\Accounts\Account> $data */
		$data = $result->toArray();

		return $data;
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Accounts\Account>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function getResultSet(
		Queries\FindAccounts $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		/** @var DoctrineOrmQuery\ResultSet<Entities\Accounts\Account> $result */
		$result = $queryObject->fetch($this->getRepository());

		return $result;
	}

	/**
	 * @phpstan-param class-string<Entities\Accounts\Account> $type
	 *
	 * @phpstan-return ORM\EntityRepository<Entities\Accounts\Account>
	 */
	private function getRepository(string $type = Entities\Accounts\Account::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
