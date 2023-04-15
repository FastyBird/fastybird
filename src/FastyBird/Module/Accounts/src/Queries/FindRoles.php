<?php declare(strict_types = 1);

/**
 * FindRoles.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Queries;

use Closure;
use Doctrine\ORM;
use FastyBird\Module\Accounts\Entities;
use IPub\DoctrineOrmQuery;
use Ramsey\Uuid;

/**
 * Find roles entities query
 *
 * @extends  DoctrineOrmQuery\QueryObject<Entities\Roles\Role>
 *
 * @package          FastyBird:AccountsModule!
 * @subpackage       Queries
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindRoles extends DoctrineOrmQuery\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('r.id = :id')
				->setParameter('id', $id->getBytes());
		};
	}

	public function byName(string $name): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($name): void {
			$qb->andWhere('r.name = :name')
				->setParameter('name', $name);
		};
	}

	public function forParent(Entities\Roles\Role $role): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('r.parent', 'parent');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($role): void {
			$qb->andWhere('parent.id = :parent')
				->setParameter('parent', $role->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Roles\Role> $repository
	 */
	protected function doCreateQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		return $this->createBasicDql($repository);
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Roles\Role> $repository
	 */
	private function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('r');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Roles\Role> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		return $this->createBasicDql($repository)
			->select('COUNT(r.id)');
	}

}
