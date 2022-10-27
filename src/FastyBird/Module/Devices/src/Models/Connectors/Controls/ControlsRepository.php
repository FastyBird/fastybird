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

namespace FastyBird\Module\Devices\Models\Connectors\Controls;

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
 * Connector control structure repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ControlsRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Connectors\Controls\Control>|null */
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
	public function findOneBy(Queries\FindConnectorControls $queryObject): Entities\Connectors\Controls\Control|null
	{
		return $this->database->query(
			fn (): Entities\Connectors\Controls\Control|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @phpstan-return Array<Entities\Connectors\Controls\Control>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(Queries\FindConnectorControls $queryObject): array
	{
		return $this->database->query(
			function () use ($queryObject): array {
				/** @var Array<Entities\Connectors\Controls\Control>|DoctrineOrmQuery\ResultSet<Entities\Connectors\Controls\Control> $result */
				$result = $queryObject->fetch($this->getRepository());

				if (is_array($result)) {
					return $result;
				}

				/** @var Array<Entities\Connectors\Controls\Control> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Connectors\Controls\Control>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\FindConnectorControls $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject): DoctrineOrmQuery\ResultSet {
				/** @var DoctrineOrmQuery\ResultSet<Entities\Connectors\Controls\Control> $result */
				$result = $queryObject->fetch($this->getRepository());

				return $result;
			},
		);
	}

	/**
	 * @param class-string<Entities\Connectors\Controls\Control> $type
	 *
	 * @return ORM\EntityRepository<Entities\Connectors\Controls\Control>
	 */
	private function getRepository(string $type = Entities\Connectors\Controls\Control::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
