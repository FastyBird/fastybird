<?php declare(strict_types = 1);

/**
 * TriggersRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\TriggersModule\Models\Triggers;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\TriggersModule\Entities;
use FastyBird\TriggersModule\Exceptions;
use FastyBird\TriggersModule\Queries;
use IPub\DoctrineOrmQuery;
use Nette;
use Throwable;

/**
 * Trigger repository
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TriggersRepository implements ITriggersRepository
{

	use Nette\SmartObject;

	/**
	 * @var ORM\EntityRepository[]
	 *
	 * @phpstan-var ORM\EntityRepository<Entities\Triggers\ITrigger>[]
	 */
	private array $repository = [];

	/** @var Persistence\ManagerRegistry */
	private Persistence\ManagerRegistry $managerRegistry;

	public function __construct(Persistence\ManagerRegistry $managerRegistry)
	{
		$this->managerRegistry = $managerRegistry;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOneBy(
		Queries\FindTriggersQuery $queryObject,
		string $type = Entities\Triggers\Trigger::class
	): ?Entities\Triggers\ITrigger {
		/** @var Entities\Triggers\ITrigger|null $trigger */
		$trigger = $queryObject->fetchOne($this->getRepository($type));

		return $trigger;
	}

	/**
	 * @param string $type
	 *
	 * @return ORM\EntityRepository
	 *
	 * @phpstan-param class-string $type
	 *
	 * @phpstan-return ORM\EntityRepository<Entities\Triggers\ITrigger>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$repository = $this->managerRegistry->getRepository($type);

			if (!$repository instanceof ORM\EntityRepository) {
				throw new Exceptions\InvalidStateException('Entity repository could not be loaded');
			}

			$this->repository[$type] = $repository;
		}

		return $this->repository[$type];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Throwable
	 */
	public function findAllBy(
		Queries\FindTriggersQuery $queryObject,
		string $type = Entities\Triggers\Trigger::class
	): array {
		$result = $queryObject->fetch($this->getRepository($type));

		return is_array($result) ? $result : $result->toArray();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Throwable
	 */
	public function getResultSet(
		Queries\FindTriggersQuery $queryObject,
		string $type = Entities\Triggers\Trigger::class
	): DoctrineOrmQuery\ResultSet {
		$result = $queryObject->fetch($this->getRepository($type));

		if (!$result instanceof DoctrineOrmQuery\ResultSet) {
			throw new Exceptions\InvalidStateException('Result set for given query could not be loaded.');
		}

		return $result;
	}

}
