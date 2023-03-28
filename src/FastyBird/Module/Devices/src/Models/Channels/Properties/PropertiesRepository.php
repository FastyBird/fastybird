<?php declare(strict_types = 1);

/**
 * PropertiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           21.11.18
 */

namespace FastyBird\Module\Devices\Models\Channels\Properties;

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

	/** @var array<ORM\EntityRepository<Entities\Channels\Properties\Property>> */
	private array $repository = [];

	public function __construct(
		private readonly Utilities\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @phpstan-param class-string<Entities\Channels\Properties\Property> $type
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\FindChannelProperties $queryObject,
		string $type = Entities\Channels\Properties\Property::class,
	): Entities\Channels\Properties\Property|null
	{
		return $this->database->query(
			fn (): Entities\Channels\Properties\Property|null => $queryObject->fetchOne($this->getRepository($type)),
		);
	}

	/**
	 * @phpstan-param class-string<Entities\Channels\Properties\Property> $type
	 *
	 * @phpstan-return array<Entities\Channels\Properties\Property>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\FindChannelProperties $queryObject,
		string $type = Entities\Channels\Properties\Property::class,
	): array
	{
		return $this->database->query(
			function () use ($queryObject, $type): array {
				/** @var array<Entities\Channels\Properties\Property>|DoctrineOrmQuery\ResultSet<Entities\Channels\Properties\Property> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				if (is_array($result)) {
					return $result;
				}

				/** @var array<Entities\Channels\Properties\Property> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @phpstan-param class-string<Entities\Channels\Properties\Property> $type
	 *
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Channels\Properties\Property>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\FindChannelProperties $queryObject,
		string $type = Entities\Channels\Properties\Property::class,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject, $type): DoctrineOrmQuery\ResultSet {
				/** @var DoctrineOrmQuery\ResultSet<Entities\Channels\Properties\Property> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				return $result;
			},
		);
	}

	/**
	 * @param class-string<Entities\Channels\Properties\Property> $type
	 *
	 * @return ORM\EntityRepository<Entities\Channels\Properties\Property>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$this->repository[$type] = $this->managerRegistry->getRepository($type);
		}

		return $this->repository[$type];
	}

}
