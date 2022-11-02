<?php declare(strict_types = 1);

/**
 * ConditionsRepository.php
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

namespace FastyBird\Module\Triggers\Models\Conditions;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Utilities;
use IPub\DoctrineOrmQuery;
use Nette;
use function is_array;

/**
 * Condition repository
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConditionsRepository
{

	use Nette\SmartObject;

	/** @var Array<ORM\EntityRepository<Entities\Conditions\Condition>> */
	private array $repository = [];

	public function __construct(
		private readonly Utilities\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @param class-string<Entities\Conditions\Condition> $type
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\FindConditions $queryObject,
		string $type = Entities\Conditions\Condition::class,
	): Entities\Conditions\Condition|null
	{
		return $this->database->query(
			fn (): Entities\Conditions\Condition|null => $queryObject->fetchOne($this->getRepository($type)),
		);
	}

	/**
	 * @param class-string<Entities\Conditions\Condition> $type
	 *
	 * @return Array<Entities\Conditions\Condition>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\FindConditions $queryObject,
		string $type = Entities\Conditions\Condition::class,
	): array
	{
		return $this->database->query(
			function () use ($queryObject, $type): array {
				/** @var Array<Entities\Conditions\Condition>|DoctrineOrmQuery\ResultSet<Entities\Conditions\Condition> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				if (is_array($result)) {
					return $result;
				}

				/** @var Array<Entities\Conditions\Condition> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @phpstan-param class-string<Entities\Conditions\Condition> $type
	 *
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Conditions\Condition>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\FindConditions $queryObject,
		string $type = Entities\Conditions\Condition::class,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject, $type): DoctrineOrmQuery\ResultSet {
				/** @var DoctrineOrmQuery\ResultSet<Entities\Conditions\Condition> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				return $result;
			},
		);
	}

	/**
	 * @param class-string<Entities\Conditions\Condition> $type
	 *
	 * @return ORM\EntityRepository<Entities\Conditions\Condition>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$this->repository[$type] = $this->managerRegistry->getRepository($type);
		}

		return $this->repository[$type];
	}

}
