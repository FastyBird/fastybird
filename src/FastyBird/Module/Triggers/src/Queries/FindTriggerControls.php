<?php declare(strict_types = 1);

/**
 * FindTriggerControlsQuery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Queries
 * @since          0.4.0
 *
 * @date           01.10.21
 */

namespace FastyBird\Module\Triggers\Queries;

use Closure;
use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use IPub\DoctrineOrmQuery;
use Ramsey\Uuid;

/**
 * Find trigger properties entities query
 *
 * @package          FastyBird:TriggersModule!
 * @subpackage       Queries
 *
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends  DoctrineOrmQuery\QueryObject<Entities\Triggers\Controls\IControl>
 */
class FindTriggerControlsQuery extends DoctrineOrmQuery\QueryObject
{

	/** @var Closure[] */
	private array $filter = [];

	/** @var Closure[] */
	private array $select = [];

	/**
	 * @param Uuid\UuidInterface $id
	 *
	 * @return void
	 */
	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('c.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	public function byKey(string $key): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($key): void {
			$qb->andWhere('c.key = :key')->setParameter('key', $key);
		};
	}

	/**
	 * @param string $identifier
	 *
	 * @return void
	 */
	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('c.identifier = :identifier')->setParameter('identifier', $identifier);
		};
	}

	/**
	 * @param Entities\Triggers\ITrigger $trigger
	 *
	 * @return void
	 */
	public function forTrigger(Entities\Triggers\ITrigger $trigger): void
	{
		$this->filter[] = function (ORM\QueryBuilder $qb) use ($trigger): void {
			$qb->andWhere('trigger.id = :trigger')
				->setParameter('trigger', $trigger->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @param string $sortBy
	 * @param string $sortDir
	 *
	 * @return void
	 */
	public function sortBy(string $sortBy, string $sortDir = Common\Collections\Criteria::ASC): void
	{
		if (!in_array($sortDir, [Common\Collections\Criteria::ASC, Common\Collections\Criteria::DESC], true)) {
			throw new Exceptions\InvalidArgumentException('Provided sortDir value is not valid.');
		}

		$this->filter[] = function (ORM\QueryBuilder $qb) use ($sortBy, $sortDir): void {
			$qb->addOrderBy($sortBy, $sortDir);
		};
	}

	/**
	 * @param ORM\EntityRepository $repository
	 *
	 * @return ORM\QueryBuilder
	 *
	 * @phpstan-param ORM\EntityRepository<Entities\Triggers\Controls\IControl> $repository
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
	 * @phpstan-param ORM\EntityRepository<Entities\Triggers\Controls\IControl> $repository
	 */
	private function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('c');
		$qb->addSelect('trigger');
		$qb->join('c.trigger', 'trigger');

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
	 * @phpstan-param ORM\EntityRepository<Entities\Triggers\Controls\IControl> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository)->select('COUNT(c.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
