<?php declare(strict_types = 1);

/**
 * ActionsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbTriggersModule!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbTriggersModule\Models;

use FastyBird\Bridge\RedisDbTriggersModule\States;
use FastyBird\Module\Triggers\Models as TriggersModels;
use FastyBird\Module\Triggers\States as TriggersStates;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use function assert;

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

	/** @var RedisDbModels\StatesManager<States\Action> */
	private RedisDbModels\StatesManager $statesManager;

	/**
	 * @phpstan-param  RedisDbModels\StatesManagerFactory<States\Action> $statesManagerFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\StatesManagerFactory $statesManagerFactory,
		private readonly int $database = 0,
	)
	{
		$this->statesManager = $statesManagerFactory->create(States\Action::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Action
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function update(TriggersStates\Action $state, Utils\ArrayHash $values): States\Action
	{
		assert($state instanceof States\Action);

		return $this->statesManager->update($state, $values, $this->database);
	}

	public function delete(TriggersStates\Action $state): bool
	{
		assert($state instanceof States\Action);

		return $this->statesManager->delete($state, $this->database);
	}

}
