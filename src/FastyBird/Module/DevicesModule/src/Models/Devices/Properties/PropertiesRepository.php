<?php declare(strict_types = 1);

/**
 * PropertiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           21.11.18
 */

namespace FastyBird\DevicesModule\Models\Devices\Properties;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Queries;
use IPub\DoctrineOrmQuery;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Throwable;
use function is_array;

/**
 * Device channel property structure repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertiesRepository
{

	use Nette\SmartObject;

	/** @var Array<ORM\EntityRepository<Entities\Devices\Properties\Property>> */
	private array $repository = [];

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * @phpstan-param class-string $type
	 *
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findOneBy(
		Queries\FindDeviceProperties $queryObject,
		string $type = Entities\Devices\Properties\Property::class,
	): Entities\Devices\Properties\Property|null
	{
		return $queryObject->fetchOne($this->getRepository($type));
	}

	/**
	 * @phpstan-param class-string $type
	 *
	 * @phpstan-return  Array<Entities\Devices\Properties\Property>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Throwable
	 */
	public function findAllBy(
		Queries\FindDeviceProperties $queryObject,
		string $type = Entities\Devices\Properties\Property::class,
	): array
	{
		/** @var Array<Entities\Devices\Properties\Property>|DoctrineOrmQuery\ResultSet<Entities\Devices\Properties\Property> $result */
		$result = $queryObject->fetch($this->getRepository($type));

		if (is_array($result)) {
			return $result;
		}

		/** @var Array<Entities\Devices\Properties\Property> $data */
		$data = $result->toArray();

		return $data;
	}

	/**
	 * @phpstan-param class-string $type
	 *
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Devices\Properties\Property>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function getResultSet(
		Queries\FindDeviceProperties $queryObject,
		string $type = Entities\Devices\Properties\Property::class,
	): DoctrineOrmQuery\ResultSet
	{
		/** @var DoctrineOrmQuery\ResultSet<Entities\Devices\Properties\Property> $result */
		$result = $queryObject->fetch($this->getRepository($type));

		return $result;
	}

	/**
	 * @param class-string $type
	 *
	 * @return ORM\EntityRepository<Entities\Devices\Properties\Property>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			/** @var  ORM\EntityRepository<Entities\Devices\Properties\Property> $repository */
			$repository = $this->managerRegistry->getRepository($type);

			$this->repository[$type] = $repository;
		}

		return $this->repository[$type];
	}

}
