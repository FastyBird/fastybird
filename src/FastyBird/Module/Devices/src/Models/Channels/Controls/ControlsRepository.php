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

namespace FastyBird\Module\Devices\Models\Channels\Controls;

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
 * Device channel control structure repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ControlsRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Channels\Controls\Control>|null */
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
	public function findOneBy(Queries\FindChannelControls $queryObject): Entities\Channels\Controls\Control|null
	{
		return $this->database->query(
			fn (): Entities\Channels\Controls\Control|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @phpstan-return Array<Entities\Channels\Controls\Control>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(Queries\FindChannelControls $queryObject): array
	{
		return $this->database->query(
			function () use ($queryObject): array {
				/** @var Array<Entities\Channels\Controls\Control>|DoctrineOrmQuery\ResultSet<Entities\Channels\Controls\Control> $result */
				$result = $queryObject->fetch($this->getRepository());

				if (is_array($result)) {
					return $result;
				}

				/** @var Array<Entities\Channels\Controls\Control> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Channels\Controls\Control>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function getResultSet(
		Queries\FindChannelControls $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		/** @var DoctrineOrmQuery\ResultSet<Entities\Channels\Controls\Control> $result */
		$result = $queryObject->fetch($this->getRepository());

		return $result;
	}

	/**
	 * @param class-string<Entities\Channels\Controls\Control> $type
	 *
	 * @return ORM\EntityRepository<Entities\Channels\Controls\Control>
	 */
	private function getRepository(string $type = Entities\Channels\Controls\Control::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
