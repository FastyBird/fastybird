<?php declare(strict_types = 1);

/**
 * ConditionsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\States;
use Nette;
use Nette\Utils;
use function array_merge;

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

	public function __construct(
		protected readonly ExchangeEntities\EntityFactory $entityFactory,
		protected readonly IConditionsManager|null $manager = null,
		protected readonly ExchangePublisher\Publisher|null $publisher = null,
	)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	public function create(
		Entities\Conditions\Condition $condition,
		Utils\ArrayHash $values,
	): States\Condition
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Condition state manager is not registered');
		}

		$createdState = $this->manager->create($condition->getId(), $values);

		$this->publishEntity($condition, $createdState);

		return $createdState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	public function update(
		Entities\Conditions\Condition $condition,
		States\Condition $state,
		Utils\ArrayHash $values,
	): States\Condition
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Condition state manager is not registered');
		}

		$updatedState = $this->manager->update($state, $values);

		$this->publishEntity($condition, $updatedState);

		return $updatedState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	public function delete(
		Entities\Conditions\Condition $condition,
		States\Condition $state,
	): bool
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Condition state manager is not registered');
		}

		$result = $this->manager->delete($state);

		if ($result) {
			$this->publishEntity($condition, null);
		}

		return $result;
	}

	/**
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	private function publishEntity(
		Entities\Conditions\Condition $condition,
		States\Condition|null $state,
	): void
	{
		if ($this->publisher === null) {
			return;
		}

		$this->publisher->publish(
			$condition->getSource(),
			MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_UPDATED),
			$this->entityFactory->create(Utils\Json::encode(array_merge($condition->toArray(), [
				'is_fulfilled' => !($state === null) && $state->isFulfilled(),
			])), MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::ROUTE_TRIGGER_CONDITION_ENTITY_UPDATED,
			)),
		);
	}

}
