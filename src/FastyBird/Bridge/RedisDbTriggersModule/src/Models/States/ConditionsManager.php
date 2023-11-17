<?php declare(strict_types = 1);

/**
 * ConditionsManager.php
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
use FastyBird\Module\Triggers\States as TriggersStates;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use function assert;

/**
 * Condition states manager
 *
 * @package        FastyBird:RedisDbTriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConditionsManager implements TriggersModels\States\IConditionsManager
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesManager<States\Condition> */
	private RedisDbModels\States\StatesManager $statesManager;

	/**
	 * @param RedisDbModels\States\StatesManagerFactory<States\Condition> $statesManagerFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesManagerFactory $statesManagerFactory,
		private readonly int $database = 0,
	)
	{
		$this->statesManager = $statesManagerFactory->create(States\Condition::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Condition
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function update(TriggersStates\Condition $state, Utils\ArrayHash $values): States\Condition
	{
		assert($state instanceof States\Condition);

		return $this->statesManager->update($state, $values, $this->database);
	}

	public function delete(TriggersStates\Condition $state): bool
	{
		assert($state instanceof States\Condition);

		return $this->statesManager->delete($state, $this->database);
	}

}
