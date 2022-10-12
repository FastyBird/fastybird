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

namespace FastyBird\DevicesModule\Models\Connectors\Controls;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Queries;
use IPub\DoctrineOrmQuery;
use Nette;
use Throwable;
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

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	public function findOneBy(Queries\FindConnectorControls $queryObject): Entities\Connectors\Controls\Control|null
	{
		return $queryObject->fetchOne($this->getRepository());
	}

	/**
	 * @return Array<Entities\Connectors\Controls\Control>
	 *
	 * @throws Throwable
	 */
	public function findAllBy(Queries\FindConnectorControls $queryObject): array
	{
		/** @var Array<Entities\Connectors\Controls\Control>|DoctrineOrmQuery\ResultSet<Entities\Connectors\Controls\Control> $result */
		$result = $queryObject->fetch($this->getRepository());

		if (is_array($result)) {
			return $result;
		}

		/** @var Array<Entities\Connectors\Controls\Control> $data */
		$data = $result->toArray();

		return $data;
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Connectors\Controls\Control>
	 */
	public function getResultSet(
		Queries\FindConnectorControls $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		$result = $queryObject->fetch($this->getRepository());

		if (!$result instanceof DoctrineOrmQuery\ResultSet) {
			throw new Exceptions\InvalidState('Result set for given query could not be loaded.');
		}

		return $result;
	}

	/**
	 * @param class-string $type
	 *
	 * @return ORM\EntityRepository<Entities\Connectors\Controls\Control>
	 */
	private function getRepository(string $type = Entities\Connectors\Controls\Control::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			/** @var ORM\EntityRepository<Entities\Connectors\Controls\Control> $repository */
			$repository = $this->managerRegistry->getRepository($type);

			if (!$repository instanceof ORM\EntityRepository) {
				throw new Exceptions\InvalidState('Entity repository could not be loaded');
			}

			$this->repository = $repository;
		}

		return $this->repository;
	}

}
