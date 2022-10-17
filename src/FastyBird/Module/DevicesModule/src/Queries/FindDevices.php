<?php declare(strict_types = 1);

/**
 * FindDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          0.1.0
 *
 * @date           30.07.18
 */

namespace FastyBird\DevicesModule\Queries;

use Closure;
use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Exceptions;
use IPub\DoctrineOrmQuery;
use Ramsey\Uuid;
use function in_array;

/**
 * Find devices entities query
 *
 * @extends  DoctrineOrmQuery\QueryObject<Entities\Devices\Device>
 *
 * @package          FastyBird:DevicesModule!
 * @subpackage       Queries
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDevices extends DoctrineOrmQuery\QueryObject
{

	/** @var Array<Closure(ORM\QueryBuilder $qb): void> */
	private array $filter = [];

	/** @var Array<Closure(ORM\QueryBuilder $qb): void> */
	private array $select = [];

	public function byId(Uuid\UuidInterface $id): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($id): void {
			$qb->andWhere('d.id = :id')->setParameter('id', $id, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function byIdentifier(string $identifier): void
	{
		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($identifier): void {
			$qb->andWhere('d.identifier = :identifier')->setParameter('identifier', $identifier);
		};
	}

	public function byConnectorId(Uuid\UuidInterface $connectorId): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->addSelect('connector');
			$qb->join('d.connector', 'connector');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($connectorId): void {
			$qb->andWhere('connector.id = :connector')
				->setParameter('connector', $connectorId, Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forConnector(Entities\Connectors\Connector $connector): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->addSelect('connector');
			$qb->join('d.connector', 'connector');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($connector): void {
			$qb->andWhere('connector.id = :connector')
				->setParameter('connector', $connector->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forParent(Entities\Devices\Device $device): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->innerJoin('d.parents', 'dp');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($device): void {
			$qb->andWhere('dp.id = :parentDevice')
				->setParameter('parentDevice', $device->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function forChild(Entities\Devices\Device $device): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->innerJoin('d.children', 'dch');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb) use ($device): void {
			$qb->andWhere('dch.id = :childDevice')
				->setParameter('childDevice', $device->getId(), Uuid\Doctrine\UuidBinaryType::NAME);
		};
	}

	public function withSettableChannelProperties(): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('d.channels', 'channels');
			$qb->join('channels.properties', 'chProperties');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('chProperties.settable = :settable')->setParameter('settable', true);
		};
	}

	public function withChannels(): void
	{
		$this->select[] = static function (ORM\QueryBuilder $qb): void {
			$qb->join('d.channels', 'channels');
		};

		$this->filter[] = static function (ORM\QueryBuilder $qb): void {
			$qb->andWhere('SIZE(channels.children) = 0');
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
	 * @phpstan-param ORM\EntityRepository<Entities\Devices\Device> $repository
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
	 * @phpstan-param ORM\EntityRepository<Entities\Devices\Device> $repository
	 */
	protected function createBasicDql(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $repository->createQueryBuilder('d');

		// $qb->select('nd');
		// $qb->leftJoin(Entities\Devices\NetworkDevice::class, 'nd', ORM\Query\Expr\Join::WITH, 'd = nd');

		// $qb->select('ld');
		// $qb->leftJoin(Entities\Devices\LocalDevice::class, 'ld', ORM\Query\Expr\Join::WITH, 'd = ld');

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

	/**
	 * @phpstan-param ORM\EntityRepository<Entities\Devices\Device> $repository
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository): ORM\QueryBuilder
	{
		$qb = $this->createBasicDql($repository)->select('COUNT(d.id)');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
