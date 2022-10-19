<?php declare(strict_types = 1);

/**
 * DevicesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           28.07.18
 */

namespace FastyBird\Module\Devices\Models\Devices;

use Doctrine\ORM;
use Doctrine\Persistence;
use Exception;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Queries;
use IPub\DoctrineOrmQuery;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use function is_array;

/**
 * Device repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicesRepository
{

	use Nette\SmartObject;

	/** @var Array<ORM\EntityRepository<Entities\Devices\Device>> */
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
		Queries\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
	): Entities\Devices\Device|null
	{
		return $queryObject->fetchOne($this->getRepository($type));
	}

	/**
	 * @phpstan-param class-string $type
	 *
	 * @phpstan-return Array<Entities\Devices\Device>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findAllBy(
		Queries\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
	): array
	{
		/** @var Array<Entities\Devices\Device>|DoctrineOrmQuery\ResultSet<Entities\Devices\Device> $result */
		$result = $queryObject->fetch($this->getRepository($type));

		if (is_array($result)) {
			return $result;
		}

		/** @var Array<Entities\Devices\Device> $data */
		$data = $result->toArray();

		return $data;
	}

	/**
	 * @phpstan-param class-string $type
	 *
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Devices\Device>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function getResultSet(
		Queries\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
	): DoctrineOrmQuery\ResultSet
	{
		/** @var DoctrineOrmQuery\ResultSet<Entities\Devices\Device> $result */
		$result = $queryObject->fetch($this->getRepository($type));

		return $result;
	}

	/**
	 * @param class-string $type
	 *
	 * @return ORM\EntityRepository<Entities\Devices\Device>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			/** @var ORM\EntityRepository<Entities\Devices\Device> $repository */
			$repository = $this->managerRegistry->getRepository($type);

			$this->repository[$type] = $repository;
		}

		return $this->repository[$type];
	}

}
