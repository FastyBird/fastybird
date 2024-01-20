<?php declare(strict_types = 1);

/**
 * ActionsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbTriggersModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbTriggersModule\Models\States;

use FastyBird\Bridge\RedisDbTriggersModule\States;
use FastyBird\Module\Triggers\Models as TriggersModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Action states manager
 *
 * @package        FastyBird:RedisDbTriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ActionsManager implements TriggersModels\States\IActionsManager
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesManager<States\Action> */
	private RedisDbModels\States\StatesManager $statesManager;

	/**
	 * @param RedisDbModels\States\StatesManagerFactory<States\Action> $statesManagerFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesManagerFactory $statesManagerFactory,
		private readonly int $database = 0,
	)
	{
		$this->statesManager = $statesManagerFactory->create(States\Action::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Action
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Action|false
	{
		return $this->statesManager->update($id, $values, $this->database);
	}

	public function delete(Uuid\UuidInterface $id): bool
	{
		return $this->statesManager->delete($id, $this->database);
	}

}
