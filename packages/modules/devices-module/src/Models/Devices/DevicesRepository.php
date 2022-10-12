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

namespace FastyBird\DevicesModule\Models\Devices;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Queries;
use IPub\DoctrineOrmQuery;
use Nette;
use Throwable;
use function assert;
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
	 * @param class-string $type
	 */
	public function findOneBy(
		Queries\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
	): Entities\Devices\Device|null
	{
		/** @var mixed $device */
		$device = $queryObject->fetchOne($this->getRepository($type));
		assert($device instanceof Entities\Devices\Device || $device === null);

		return $device;
	}

	/**
	 * @param class-string $type
	 *
	 * @return Array<Entities\Devices\Device>
	 *
	 * @throws Throwable
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
	 */
	public function getResultSet(
		Queries\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
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
	 * @return ORM\EntityRepository<Entities\Devices\Device>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			/** @var ORM\EntityRepository<Entities\Devices\Device> $repository */
			$repository = $this->managerRegistry->getRepository($type);

			if (!$repository instanceof ORM\EntityRepository) {
				throw new Exceptions\InvalidState('Entity repository could not be loaded');
			}

			$this->repository[$type] = $repository;
		}

		return $this->repository[$type];
	}

}
