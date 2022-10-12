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
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Queries;
use IPub\DoctrineOrmQuery;
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
	 * @param class-string $type
	 */
	public function findOneBy(
		Queries\FindDeviceProperties $queryObject,
		string $type = Entities\Devices\Properties\Property::class,
	): Entities\Devices\Properties\Property|null
	{
		return $queryObject->fetchOne($this->getRepository($type));
	}

	/**
	 * @param class-string $type
	 *
	 * @return Array<Entities\Devices\Properties\Property>
	 *
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
	 */
	public function getResultSet(
		Queries\FindDeviceProperties $queryObject,
		string $type = Entities\Devices\Properties\Property::class,
	): DoctrineOrmQuery\ResultSet
	{
		$result = $queryObject->fetch($this->getRepository($type));

		if (!$result instanceof DoctrineOrmQuery\ResultSet) {
			throw new Exceptions\InvalidState('Result set for given query could not be loaded.');
		}

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

			if (!$repository instanceof ORM\EntityRepository) {
				throw new Exceptions\InvalidState('Entity repository could not be loaded');
			}

			$this->repository[$type] = $repository;
		}

		return $this->repository[$type];
	}

}
