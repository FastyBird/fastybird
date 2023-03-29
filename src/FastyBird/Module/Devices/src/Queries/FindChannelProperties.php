<?php declare(strict_types = 1);

/**
 * FindChannelProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           25.11.18
 */

namespace FastyBird\Module\Devices\Queries;

use Closure;
use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use IPub\DoctrineOrmQuery;
use Ramsey\Uuid;
use function in_array;

/**
 * Find channel properties entities query
 *
 * @extends  DoctrineOrmQuery\QueryObject<Entities\Channels\Properties\Property>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindChannelProperties extends DoctrineOrmQuery\QueryObject
{

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('p.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('p.identifier = :identifier')->setParameter('identifier', $identifier);
		};
	}

	public function forChannel(Entities\Channels\Channel $channel): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($channel): void {
			$qb->andWhere('channel.id = :channel')
				->setParameter('channel', $channel->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byChannelId(Uuid\UuidInterface $channelId): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($channelId): void {
			$qb->andWhere('channel.id = :channel')
				->setParameter('channel', $channelId, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forParent(Entities\Channels\Properties\Property $property): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($property): void {
			$qb->andWhere('p.parent = :parent')
				->setParameter('parent', $property->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function sortBy(string $sortBy, string $sortDir = Common\Collections\Criteria::ASC): void
	{
		if (!in_array($sortDir, [Common\Collections\Criteria::ASC, Common\Collections\Criteria::DESC], true)) {
			throw new Exceptions\InvalidArgument('Provided sortDir value is not valid.');
		}

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($sortBy, $sortDir): void {
			$qb->addOrderBy($sortBy, $sortDir);
		};
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Channels\Properties\Property> $repository
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
	 * @phpstan-param ORM\EntityRepository<Entities\Channels\Properties\Property> $repository
	 */
	private function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('p');
		$qb->addSelect('channel');
		$qb->join('p.channel', 'channel');

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Channels\Properties\Property> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository)->select('COUNT(p.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
