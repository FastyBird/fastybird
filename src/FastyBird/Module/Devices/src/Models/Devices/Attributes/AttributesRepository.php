<?php declare(strict_types = 1);

/**
 * AttributesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.57.0
 *
 * @date           22.04.22
 */

namespace FastyBird\Module\Devices\Models\Devices\Attributes;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Utilities;
use IPub\DoctrineOrmQuery;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use function is_array;

/**
 * Device attribute structure repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AttributesRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Devices\Attributes\Attribute>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(
		private readonly Utilities\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(Queries\FindDeviceAttributes $queryObject): Entities\Devices\Attributes\Attribute|null
	{
		return $this->database->query(
			fn (): Entities\Devices\Attributes\Attribute|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @phpstan-return Array<Entities\Devices\Attributes\Attribute>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(Queries\FindDeviceAttributes $queryObject): array
	{
		return $this->database->query(
			function () use ($queryObject): array {
				/** @var Array<Entities\Devices\Attributes\Attribute>|DoctrineOrmQuery\ResultSet<Entities\Devices\Attributes\Attribute> $result */
				$result = $queryObject->fetch($this->getRepository());

				if (is_array($result)) {
					return $result;
				}

				/** @var Array<Entities\Devices\Attributes\Attribute> $data */
				$data = $result->toArray();

				return $data;
			},
		);
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
	 * @param class-string<Entities\Devices\Attributes\Attribute> $type
	 *
	 * @return ORM\EntityRepository<Entities\Devices\Attributes\Attribute>
	 */
	private function getRepository(string $type = Entities\Devices\Attributes\Attribute::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
