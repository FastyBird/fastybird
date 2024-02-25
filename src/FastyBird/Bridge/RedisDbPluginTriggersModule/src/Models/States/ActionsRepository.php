<?php declare(strict_types = 1);

/**
 * ActionsRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPluginTriggersModuleBridge!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           12.01.22
 */

namespace FastyBird\Bridge\RedisDbPluginTriggersModule\Models\States;

use FastyBird\Bridge\RedisDbPluginTriggersModule\States;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Models as TriggersModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Ramsey\Uuid;

/**
 * Trigger action state repository
 *
 * @package        FastyBird:RedisDbPluginTriggersModuleBridge!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ActionsRepository implements TriggersModels\States\IActionsRepository
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesRepository<States\Action> */
	private RedisDbModels\States\StatesRepository $stateRepository;

	/**
	 * @param RedisDbModels\States\StatesRepositoryFactory<States\Action> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\Action::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOne(
		TriggersDocuments\Actions\Action|TriggersEntities\Actions\Action $action,
	): States\Action|null
	{
		return $this->stateRepository->find($action->getId(), $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOneById(Uuid\UuidInterface $id): States\Action|null
	{
		return $this->stateRepository->find($id, $this->database);
	}

}
