<?php declare(strict_types = 1);

/**
 * FindTriggersQuery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Queries
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Queries;

use Closure;
use Doctrine\ORM;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineOrmQuery;
use Ramsey\Uuid;

/**
 * Find trigger entities query
 *
 * @package          FastyBird:TriggersModule!
 * @subpackage       Queries
 *
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends DoctrineOrmQuery\QueryObject<Entities\Triggers\ITrigger>
 */
class FindTriggersQuery extends DoctrineOrmQuery\QueryObject
{

	/** @var Closure[] */
	protected array $filter = [];

	/** @var Closure[] */
	protected array $select = [];

	/**
	 * @param Uuid\UuidInterface $id
	 *
	 * @return void
	 */
	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('t.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @return void
	 */
	public function withoutConditions(): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('SIZE(t.conditions) = 0');
		};
	}

	/**
	 * @return void
	 */
	public function withoutActions(): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('SIZE(t.actions) = 0');
		};
	}

	/**
	 * @return void
	 */
	public function onlyEnabled(): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('t.enabled = :enabled')->setParameter('enabled', true);
		};
	}

	/**
	 * @param ORM\EntityRepository $repository
	 *
	 * @return ORM\QueryBuilder
	 *
	 * @phpstan-param ORM\EntityRepository<Entities\Triggers\ITrigger> $repository
	 */
	protected function doCreateQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository);

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @param ORM\EntityRepository $repository
	 *
	 * @return ORM\QueryBuilder
	 *
	 * @phpstan-param ORM\EntityRepository<Entities\Triggers\ITrigger> $repository
	 */
	protected function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('t');

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @param ORM\EntityRepository $repository
	 *
	 * @return ORM\QueryBuilder
	 *
	 * @phpstan-param ORM\EntityRepository<Entities\Triggers\ITrigger> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository)->select('COUNT(t.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
