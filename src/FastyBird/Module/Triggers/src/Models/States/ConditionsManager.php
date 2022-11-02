<?php declare(strict_types = 1);

/**
 * ConditionsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          0.17.0
 *
 * @date           08.02.22
 */

namespace FastyBird\TriggersModule\Models\States;

use FastyBird\Exchange\Entities as ExchangeEntities;
use FastyBird\Exchange\Publisher as ExchangePublisher;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\TriggersModule\Entities;
use FastyBird\TriggersModule\Exceptions;
use FastyBird\TriggersModule\Models;
use FastyBird\TriggersModule\States;
use Nette;
use Nette\Utils;

/**
 * Condition states manager
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConditionsManager
{

	use Nette\SmartObject;

	/** @var ExchangeEntities\EntityFactory */
	protected ExchangeEntities\EntityFactory $entityFactory;

	/** @var ExchangePublisher\IPublisher|null */
	protected ?ExchangePublisher\IPublisher $publisher;

	/** @var IConditionsManager|null */
	protected ?IConditionsManager $manager;

	public function __construct(
		?IConditionsManager $manager,
		ExchangeEntities\EntityFactory $entityFactory,
		?ExchangePublisher\IPublisher $publisher
	) {
		$this->manager = $manager;
		$this->entityFactory = $entityFactory;
		$this->publisher = $publisher;
	}

	/**
	 * @param Entities\Conditions\ICondition $condition
	 * @param Utils\ArrayHash $values
	 *
	 * @return States\ICondition
	 */
	public function create(
		Entities\Conditions\ICondition $condition,
		Utils\ArrayHash $values
	): States\ICondition {
		if ($this->manager === null) {
			throw new Exceptions\NotImplementedException('Condition state manager is not registered');
		}

		/** @var States\ICondition $createdState */
		$createdState = $this->manager->create($condition, $values);

		$this->publishEntity($condition, $createdState);

		return $createdState;
	}

	/**
	 * @param Entities\Conditions\ICondition $condition
	 * @param States\ICondition $state
	 * @param Utils\ArrayHash $values
	 *
	 * @return States\ICondition
	 */
	public function update(
		Entities\Conditions\ICondition $condition,
		States\ICondition $state,
		Utils\ArrayHash $values
	): States\ICondition {
		if ($this->manager === null) {
			throw new Exceptions\NotImplementedException('Condition state manager is not registered');
		}

		/** @var States\ICondition $updatedState */
		$updatedState = $this->manager->update($state, $values);

		$this->publishEntity($condition, $updatedState);

		return $updatedState;
	}

	/**
	 * @param Entities\Conditions\ICondition $condition
	 * @param States\ICondition $state
	 *
	 * @return bool
	 */
	public function delete(
		Entities\Conditions\ICondition $condition,
		States\ICondition $state
	): bool {
		if ($this->manager === null) {
			throw new Exceptions\NotImplementedException('Condition state manager is not registered');
		}

		$result = $this->manager->delete($state);

		if ($result) {
			$this->publishEntity($condition, null);
		}

		return $result;
	}

	private function publishEntity(
		Entities\Conditions\ICondition $condition,
		?States\ICondition $state
	): void {
		if ($this->publisher === null) {
			return;
		}

		$this->publisher->publish(
			$condition->getSource(),
			MetadataTypes\RoutingKeyType::get(MetadataTypes\RoutingKeyType::ROUTE_TRIGGER_CONDITION_ENTITY_UPDATED),
			$this->entityFactory->create(Utils\Json::encode(array_merge($condition->toArray(), [
				'is_fulfilled' => !($state === null) && $state->isFulfilled(),
			])), MetadataTypes\RoutingKeyType::get(MetadataTypes\RoutingKeyType::ROUTE_TRIGGER_CONDITION_ENTITY_UPDATED))
		);
	}

}
