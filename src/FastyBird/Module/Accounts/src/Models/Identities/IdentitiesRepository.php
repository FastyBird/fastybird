<?php declare(strict_types = 1);

/**
 * IdentitiesRepository.php
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

namespace FastyBird\Module\Accounts\Models\Identities;

use Doctrine\ORM;
use Doctrine\Persistence;
use Exception;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Queries;
use IPub\DoctrineOrmQuery;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use function is_array;

/**
 * Account identity facade
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class IdentitiesRepository
{

	use Nette\SmartObject;

	/** @phpstan-var ORM\EntityRepository<Entities\Identities\Identity>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 */
	public function findOneForAccount(
		Entities\Accounts\Account $account,
	): Entities\Identities\Identity|null
	{
		$findQuery = new Queries\FindIdentities();
		$findQuery->forAccount($account);
		$findQuery->inState(MetadataTypes\IdentityState::STATE_ACTIVE);

		return $this->findOneBy($findQuery);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 */
	public function findOneByUid(string $uid): Entities\Identities\Identity|null
	{
		$findQuery = new Queries\FindIdentities();
		$findQuery->byUid($uid);
		$findQuery->inState(MetadataTypes\IdentityState::STATE_ACTIVE);

		return $this->findOneBy($findQuery);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findOneBy(
		Queries\FindIdentities $queryObject,
	): Entities\Identities\Identity|null
	{
		return $queryObject->fetchOne($this->getRepository());
	}

	/**
	 * @phpstan-return array<Entities\Identities\Identity>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findAllBy(Queries\FindIdentities $queryObject): array
	{
		/** @var array<Entities\Identities\Identity>|DoctrineOrmQuery\ResultSet<Entities\Identities\Identity> $result */
		$result = $queryObject->fetch($this->getRepository());

		if (is_array($result)) {
			return $result;
		}

		/** @var array<Entities\Identities\Identity> $data */
		$data = $result->toArray();

		return $data;
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Identities\Identity>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function getResultSet(
		Queries\FindIdentities $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		/** @var DoctrineOrmQuery\ResultSet<Entities\Identities\Identity> $result */
		$result = $queryObject->fetch($this->getRepository());

		return $result;
	}

	/**
	 * @phpstan-param class-string<Entities\Identities\Identity> $type
	 *
	 * @phpstan-return ORM\EntityRepository<Entities\Identities\Identity>
	 */
	private function getRepository(string $type = Entities\Identities\Identity::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
