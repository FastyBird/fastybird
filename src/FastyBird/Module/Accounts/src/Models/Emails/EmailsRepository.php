<?php declare(strict_types = 1);

/**
 * EmailsRepository.php
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
 * Account email address repository
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EmailsRepository
{

	use Nette\SmartObject;

	/** @phpstan-var ORM\EntityRepository<Entities\Emails\Email>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findOneByAddress(string $address): Entities\Emails\Email|null
	{
		$findQuery = new Queries\FindEmails();
		$findQuery->byAddress($address);

		return $this->findOneBy($findQuery);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findOneBy(
		Queries\FindEmails $queryObject,
	): Entities\Emails\Email|null
	{
		return $queryObject->fetchOne($this->getRepository());
	}

	/**
	 * @phpstan-return array<Entities\Emails\Email>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findAllBy(Queries\FindEmails $queryObject): array
	{
		/** @var array<Entities\Emails\Email>|DoctrineOrmQuery\ResultSet<Entities\Emails\Email> $result */
		$result = $queryObject->fetch($this->getRepository());

		if (is_array($result)) {
			return $result;
		}

		/** @var array<Entities\Emails\Email> $data */
		$data = $result->toArray();

		return $data;
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Emails\Email>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function getResultSet(
		Queries\FindEmails $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		/** @var DoctrineOrmQuery\ResultSet<Entities\Emails\Email> $result */
		$result = $queryObject->fetch($this->getRepository());

		return $result;
	}

	/**
	 * @phpstan-param class-string<Entities\Emails\Email> $type
	 *
	 * @phpstan-return ORM\EntityRepository<Entities\Emails\Email>
	 */
	private function getRepository(string $type = Entities\Emails\Email::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
