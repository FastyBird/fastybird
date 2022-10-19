<?php declare(strict_types = 1);

/**
 * ConnectorsRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           16.04.21
 */

namespace FastyBird\Module\Devices\Models\Connectors;

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
 * Connector repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorsRepository
{

	use Nette\SmartObject;

	/** @var Array<ORM\EntityRepository<Entities\Connectors\Connector>> */
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
		Queries\FindConnectors $queryObject,
		string $type = Entities\Connectors\Connector::class,
	): Entities\Connectors\Connector|null
	{
		return $queryObject->fetchOne($this->getRepository($type));
	}

	/**
	 * @phpstan-param class-string $type
	 *
	 * @phpstan-return Array<Entities\Connectors\Connector>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findAllBy(
		Queries\FindConnectors $queryObject,
		string $type = Entities\Connectors\Connector::class,
	): array
	{
		/** @var Array<Entities\Connectors\Connector>|DoctrineOrmQuery\ResultSet<Entities\Connectors\Connector> $result */
		$result = $queryObject->fetch($this->getRepository($type));

		if (is_array($result)) {
			return $result;
		}

		/** @var Array<Entities\Connectors\Connector> $data */
		$data = $result->toArray();

		return $data;
	}

	/**
	 * @phpstan-param class-string $type
	 *
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Connectors\Connector>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function getResultSet(
		Queries\FindConnectors $queryObject,
		string $type = Entities\Connectors\Connector::class,
	): DoctrineOrmQuery\ResultSet
	{
		/** @var DoctrineOrmQuery\ResultSet<Entities\Connectors\Connector> $result */
		$result = $queryObject->fetch($this->getRepository($type));

		return $result;
	}

	/**
	 * @param class-string $type
	 *
	 * @return ORM\EntityRepository<Entities\Connectors\Connector>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			/** @var ORM\EntityRepository<Entities\Connectors\Connector> $repository */
			$repository = $this->managerRegistry->getRepository($type);

			$this->repository[$type] = $repository;
		}

		return $this->repository[$type];
	}

}
