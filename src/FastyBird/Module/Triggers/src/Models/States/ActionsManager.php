<?php declare(strict_types = 1);

/**
 * ActionsManager.php
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
 * Action states manager
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionsManager
{

	use Nette\SmartObject;

	/** @var ExchangeEntities\EntityFactory */
	protected ExchangeEntities\EntityFactory $entityFactory;

	/** @var ExchangePublisher\IPublisher|null */
	protected ?ExchangePublisher\IPublisher $publisher;

	/** @var IActionsManager|null */
	protected ?IActionsManager $manager;

	public function __construct(
		?IActionsManager $manager,
		ExchangeEntities\EntityFactory $entityFactory,
		?ExchangePublisher\IPublisher $publisher
	) {
		$this->manager = $manager;
		$this->entityFactory = $entityFactory;
		$this->publisher = $publisher;
	}

	/**
	 * @param Entities\Actions\IAction $action
	 * @param Utils\ArrayHash $values
	 *
	 * @return States\IAction
	 */
	public function create(
		Entities\Actions\IAction $action,
		Utils\ArrayHash $values
	): States\IAction {
		if ($this->manager === null) {
			throw new Exceptions\NotImplementedException('Action state manager is not registered');
		}

		/** @var States\IAction $createdState */
		$createdState = $this->manager->create($action, $values);

		$this->publishEntity($action, $createdState);

		return $createdState;
	}

	/**
	 * @param Entities\Actions\IAction $action
	 * @param States\IAction $state
	 * @param Utils\ArrayHash $values
	 *
	 * @return States\IAction
	 */
	public function update(
		Entities\Actions\IAction $action,
		States\IAction $state,
		Utils\ArrayHash $values
	): States\IAction {
		if ($this->manager === null) {
			throw new Exceptions\NotImplementedException('Action state manager is not registered');
		}

		/** @var States\IAction $updatedState */
		$updatedState = $this->manager->update($state, $values);

		$this->publishEntity($action, $updatedState);

		return $updatedState;
	}

	/**
	 * @param Entities\Actions\IAction $action
	 * @param States\IAction $state
	 *
	 * @return bool
	 */
	public function delete(
		Entities\Actions\IAction $action,
		States\IAction $state
	): bool {
		if ($this->manager === null) {
			throw new Exceptions\NotImplementedException('Action state manager is not registered');
		}

		$result = $this->manager->delete($state);

		if ($result) {
			$this->publishEntity($action, null);
		}

		return $result;
	}

	private function publishEntity(
		Entities\Actions\IAction $action,
		?States\IAction $state
	): void {
		if ($this->publisher === null) {
			return;
		}

		$this->publisher->publish(
			$action->getSource(),
			MetadataTypes\RoutingKeyType::get(MetadataTypes\RoutingKeyType::ROUTE_TRIGGER_ACTION_ENTITY_UPDATED),
			$this->entityFactory->create(Utils\Json::encode(array_merge($action->toArray(), [
				'is_triggered' => !($state === null) && $state->isTriggered(),
			])), MetadataTypes\RoutingKeyType::get(MetadataTypes\RoutingKeyType::ROUTE_TRIGGER_ACTION_ENTITY_UPDATED))
		);
	}

}
