<?php declare(strict_types = 1);

/**
 * FindEmails.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Queries\Entities;

use Closure;
use Doctrine\ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use IPub\DoctrineOrmQuery;
use Ramsey\Uuid;

/**
 * Find identities entities query
 *
 * @extends  DoctrineOrmQuery\QueryObject<Entities\Identities\Identity>
 *
 * @package          FastyBird:AccountsModule!
 * @subpackage       Queries
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindIdentities extends DoctrineOrmQuery\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('i.id = :id')
				->setParameter('id', $id->getBytes());
		};
	}

	public function byUid(string $uid): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($uid): void {
			$qb->andWhere('i.uid = :uid')
				->setParameter('uid', $uid);
		};
	}

	public function forAccount(Entities\Accounts\Account $account): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('i.account', 'account');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($account): void {
			$qb->andWhere('account.id = :account')
				->setParameter('account', $account->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function inState(string $state): void
	{
		if (!MetadataTypes\IdentityState::isValidValue($state)) {
			throw new Exceptions\InvalidArgument('Invalid identity state given');
		}

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($state): void {
			$qb->andWhere('i.state = :state')
				->setParameter('state', $state);
		};
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Identities\Identity> $repository
	 */
	protected function doCreateQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		return $this->createBasicDql($repository);
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Identities\Identity> $repository
	 */
	private function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('i');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Identities\Identity> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		return $this->createBasicDql($repository)
			->select('COUNT(i.id)');
	}

}
