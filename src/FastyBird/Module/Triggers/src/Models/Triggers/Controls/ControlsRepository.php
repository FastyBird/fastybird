<?php declare(strict_types = 1);

/**
 * ControlsRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          0.4.0
 *
 * @date           01.10.21
 */

namespace FastyBird\TriggersModule\Models\Triggers\Controls;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\TriggersModule\Entities;
use FastyBird\TriggersModule\Exceptions;
use FastyBird\TriggersModule\Queries;
use IPub\DoctrineOrmQuery;
use Nette;
use Throwable;

/**
 * Trigger control structure repository
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ControlsRepository implements IControlsRepository
{

	use Nette\SmartObject;

	/**
	 * @var ORM\EntityRepository|null
	 *
	 * @phpstan-var ORM\EntityRepository<Entities\Triggers\Controls\IControl>|null
	 */
	private ?ORM\EntityRepository $repository = null;

	/** @var Persistence\ManagerRegistry */
	private Persistence\ManagerRegistry $managerRegistry;

	public function __construct(Persistence\ManagerRegistry $managerRegistry)
	{
		$this->managerRegistry = $managerRegistry;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOneBy(Queries\FindTriggerControlsQuery $queryObject): ?Entities\Triggers\Controls\IControl
	{
		/** @var Entities\Triggers\Controls\IControl|null $control */
		$control = $queryObject->fetchOne($this->getRepository());

		return $control;
	}

	/**
	 * @param string $type
	 *
	 * @return ORM\EntityRepository
	 *
	 * @phpstan-param class-string $type
	 *
	 * @phpstan-return  ORM\EntityRepository<Entities\Triggers\Controls\IControl>
	 */
	private function getRepository(string $type = Entities\Triggers\Controls\Control::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$repository = $this->managerRegistry->getRepository($type);

			if (!$repository instanceof ORM\EntityRepository) {
				throw new Exceptions\InvalidStateException('Entity repository could not be loaded');
			}

			$this->repository = $repository;
		}

		return $this->repository;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Throwable
	 */
	public function getResultSet(
		Queries\FindTriggerControlsQuery $queryObject
	): DoctrineOrmQuery\ResultSet {
		$result = $queryObject->fetch($this->getRepository());

		if (!$result instanceof DoctrineOrmQuery\ResultSet) {
			throw new Exceptions\InvalidStateException('Result set for given query could not be loaded.');
		}

		return $result;
	}

}
