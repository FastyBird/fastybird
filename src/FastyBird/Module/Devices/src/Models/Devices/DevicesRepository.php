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
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Utilities;
use IPub\DoctrineOrmQuery;
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

	public function __construct(
		private readonly Utilities\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @template T of Entities\Devices\Device
	 *
	 * @phpstan-param Queries\FindDevices<T> $queryObject
	 * @phpstan-param class-string<T> $type
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
	): Entities\Devices\Device|null
	{
		return $this->database->query(
			fn (): Entities\Devices\Device|null => $queryObject->fetchOne($this->getRepository($type)),
		);
	}

	/**
	 * @template T of Entities\Devices\Device
	 *
	 * @phpstan-param Queries\FindDevices<T> $queryObject
	 * @phpstan-param class-string<T> $type
	 *
	 * @phpstan-return Array<T>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
	): array
	{
		/** @var Array<T> $result */
		$result = $this->database->query(
			function () use ($queryObject, $type): array {
				/** @var Array<T>|DoctrineOrmQuery\ResultSet<T> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				if (is_array($result)) {
					return $result;
				}

				/** @var Array<T> $data */
				$data = $result->toArray();

				return $data;
			},
		);

		return $result;
	}

	/**
	 * @template T of Entities\Devices\Device
	 *
	 * @phpstan-param Queries\FindDevices<T> $queryObject
	 * @phpstan-param class-string<T> $type
	 *
	 * @phpstan-return DoctrineOrmQuery\ResultSet<T>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\FindDevices $queryObject,
		string $type = Entities\Devices\Device::class,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject, $type): DoctrineOrmQuery\ResultSet {
				$result = $queryObject->fetch($this->getRepository($type));

				if (is_array($result)) {
					throw new Exceptions\InvalidState('Err');
				}

				return $result;
			},
		);
	}

	/**
	 * @template T of Entities\Devices\Device
	 *
	 * @phpstan-param class-string<T> $type
	 *
	 * @phpstan-return ORM\EntityRepository<T>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$this->repository[$type] = $this->managerRegistry->getRepository($type);
		}

		/** @var ORM\EntityRepository<T> $repository */
		$repository = $this->repository[$type];

		return $repository;
	}

}
