<?php declare(strict_types = 1);

/**
 * ControlsRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.4.0
 *
 * @date           29.09.21
 */

namespace FastyBird\Module\Devices\Models\Devices\Controls;

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
 * Device control structure repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ControlsRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Devices\Controls\Control>|null */
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
	public function findOneBy(Queries\FindDeviceControls $queryObject): Entities\Devices\Controls\Control|null
	{
		return $this->database->query(
			fn (): Entities\Devices\Controls\Control|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @phpstan-return Array<Entities\Devices\Controls\Control>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(Queries\FindDeviceControls $queryObject): array
	{
		return $this->database->query(
			function () use ($queryObject): array {
				/** @var Array<Entities\Devices\Controls\Control>|DoctrineOrmQuery\ResultSet<Entities\Devices\Controls\Control> $result */
				$result = $queryObject->fetch($this->getRepository());

				if (is_array($result)) {
					return $result;
				}

				/** @var Array<Entities\Devices\Controls\Control> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Devices\Controls\Control>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\FindDeviceControls $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject): DoctrineOrmQuery\ResultSet {
				/** @var DoctrineOrmQuery\ResultSet<Entities\Devices\Controls\Control> $result */
				$result = $queryObject->fetch($this->getRepository());

				return $result;
			},
		);
	}

	/**
	 * @param class-string<Entities\Devices\Controls\Control> $type
	 *
	 * @return ORM\EntityRepository<Entities\Devices\Controls\Control>
	 */
	private function getRepository(string $type = Entities\Devices\Controls\Control::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
