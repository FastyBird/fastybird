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

namespace FastyBird\DevicesModule\Models\Connectors;

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
	 * @param class-string $type
	 */
	public function findOneBy(
		Queries\FindConnectors $queryObject,
		string $type = Entities\Connectors\Connector::class,
	): Entities\Connectors\Connector|null
	{
		/** @var mixed $connector */
		$connector = $queryObject->fetchOne($this->getRepository($type));
		assert($connector instanceof Entities\Connectors\Connector || $connector === null);

		return $connector;
	}

	/**
	 * @param class-string $type
	 *
	 * @return Array<Entities\Connectors\Connector>
	 *
	 * @throws Throwable
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
	 */
	public function getResultSet(
		Queries\FindConnectors $queryObject,
		string $type = Entities\Connectors\Connector::class,
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
	 * @return ORM\EntityRepository<Entities\Connectors\Connector>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			/** @var ORM\EntityRepository<Entities\Connectors\Connector> $repository */
			$repository = $this->managerRegistry->getRepository($type);

			if (!$repository instanceof ORM\EntityRepository) {
				throw new Exceptions\InvalidState('Entity repository could not be loaded');
			}

			$this->repository[$type] = $repository;
		}

		return $this->repository[$type];
	}

}
