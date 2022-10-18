<?php declare(strict_types = 1);

/**
 * AttributesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Devices!
 * @subpackage     Models
 * @since          0.57.0
 *
 * @date           22.04.22
 */

namespace FastyBird\Module\Devices\Models\Devices\Attributes;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Queries;
use IPub\DoctrineOrmQuery;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Throwable;
use function is_array;

/**
 * Device attribute structure repository
 *
 * @package        FastyBird:Devices!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AttributesRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Devices\Attributes\Attribute>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findOneBy(Queries\FindDeviceAttributes $queryObject): Entities\Devices\Attributes\Attribute|null
	{
		return $queryObject->fetchOne($this->getRepository());
	}

	/**
	 * @phpstan-return Array<Entities\Devices\Attributes\Attribute>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Throwable
	 */
	public function findAllBy(Queries\FindDeviceAttributes $queryObject): array
	{
		/** @var Array<Entities\Devices\Attributes\Attribute>|DoctrineOrmQuery\ResultSet<Entities\Devices\Attributes\Attribute> $result */
		$result = $queryObject->fetch($this->getRepository());

		if (is_array($result)) {
			return $result;
		}

		/** @var Array<Entities\Devices\Attributes\Attribute> $data */
		$data = $result->toArray();

		return $data;
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Devices\Attributes\Attribute>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function getResultSet(
		Queries\FindDeviceAttributes $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		/** @var DoctrineOrmQuery\ResultSet<Entities\Devices\Attributes\Attribute> $result */
		$result = $queryObject->fetch($this->getRepository());

		return $result;
	}

	/**
	 * @param class-string $type
	 *
	 * @return ORM\EntityRepository<Entities\Devices\Attributes\Attribute>
	 */
	private function getRepository(string $type = Entities\Devices\Attributes\Attribute::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			/** @var ORM\EntityRepository<Entities\Devices\Attributes\Attribute> $repository */
			$repository = $this->managerRegistry->getRepository($type);

			$this->repository = $repository;
		}

		return $this->repository;
	}

}
