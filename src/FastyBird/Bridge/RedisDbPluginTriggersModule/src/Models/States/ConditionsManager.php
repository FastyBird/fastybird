<?php declare(strict_types = 1);

/**
 * ConditionsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPluginTriggersModuleBridge!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbPluginTriggersModule\Models\States;

use FastyBird\Bridge\RedisDbPluginTriggersModule\States;
use FastyBird\Module\Triggers\Models as TriggersModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Condition states manager
 *
 * @package        FastyBird:RedisDbPluginTriggersModuleBridge!
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
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Condition
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Condition|false
	{
		return $this->statesManager->update($id, $values, $this->database);
	}

	public function delete(Uuid\UuidInterface $id): bool
	{
		return $this->statesManager->delete($id, $this->database);
	}

}
