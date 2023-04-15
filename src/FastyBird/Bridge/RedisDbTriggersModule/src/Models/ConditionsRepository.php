<?php declare(strict_types = 1);

/**
 * ConditionsRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           12.01.22
 */

namespace FastyBird\Bridge\RedisDbTriggersModule\Models;

use FastyBird\Bridge\RedisDbTriggersModule\States;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Models as TriggersModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Ramsey\Uuid;

/**
 * Trigger condition state repository
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConditionsRepository implements TriggersModels\States\IConditionsRepository
{

	use Nette\SmartObject;

	/** @var RedisDbModels\StatesRepository<States\Condition> */
	private RedisDbModels\StatesRepository $stateRepository;

	/**
	 * @phpstan-param RedisDbModels\StatesRepositoryFactory<States\Condition> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\Condition::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOne(
		MetadataEntities\TriggersModule\Condition|TriggersEntities\Conditions\Condition $condition,
	): States\Condition|null
	{
		return $this->stateRepository->findOne($condition->getId(), $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOneById(Uuid\UuidInterface $id): States\Condition|null
	{
		return $this->stateRepository->findOne($id, $this->database);
	}

}
