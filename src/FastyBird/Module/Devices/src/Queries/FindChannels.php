<?php declare(strict_types = 1);

/**
 * FindChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Devices!
 * @subpackage     Queries
 * @since          0.1.0
 *
 * @date           30.07.18
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
 * Find device channels entities query
 *
 * @extends  DoctrineOrmQuery\QueryObject<Entities\Channels\Channel>
 *
 * @package          FastyBird:Devices!
 * @subpackage       Queries
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindChannels extends DoctrineOrmQuery\QueryObject
{

	/** @var Array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var Array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('ch.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('ch.identifier = :identifier')->setParameter('identifier', $identifier);
		};
	}

	public function forDevice(Entities\Devices\Device $device): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($device): void {
			$qb->andWhere('device.id = :device')
				->setParameter('device', $device->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byDeviceId(Uuid\UuidInterface $deviceId): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($deviceId): void {
			$qb->andWhere('device.id = :device')
				->setParameter('device', $deviceId, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function withProperties(): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('SIZE(ch.properties) <> 0');
		};
	}

	public function withSettableProperties(): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('ch.properties', 'properties');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('properties.settable = :settable')->setParameter('settable', true);
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
	 * @phpstan-param ORM\EntityRepository<Entities\Channels\Channel> $repository
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
	 * @phpstan-param ORM\EntityRepository<Entities\Channels\Channel> $repository
	 */
	private function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('ch');
		$qb->addSelect('device');
		$qb->join('ch.device', 'device');

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Channels\Channel> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository)->select('COUNT(ch.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
