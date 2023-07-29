<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Models\States;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use function property_exists;

/**
 * Connector property states manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorPropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		protected readonly IConnectorPropertiesManager|null $manager = null,
		protected readonly IConnectorPropertiesRepository|null $repository = null,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function create(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $values,
	): States\ConnectorProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Connector properties state manager is not registered');
		}

		if (
			property_exists($values, States\Property::ACTUAL_VALUE_KEY)
			&& property_exists($values, States\Property::EXPECTED_VALUE_KEY)
			&& $values->offsetGet(States\Property::ACTUAL_VALUE_KEY) === $values->offsetGet(
				States\Property::EXPECTED_VALUE_KEY,
			)
		) {
			$values->offsetSet(States\Property::EXPECTED_VALUE_KEY, null);
			$values->offsetSet(States\Property::PENDING_KEY, null);
		}

		$createdState = $this->manager->create($property->getId(), $values);

		$this->dispatcher?->dispatch(new Events\ConnectorPropertyStateEntityCreated($property, $createdState));

		return $createdState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function update(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		States\ConnectorProperty $state,
		Utils\ArrayHash $values,
	): States\ConnectorProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Connector properties state manager is not registered');
		}

		$updatedState = $this->manager->update($state, $values);

		if ($updatedState->getActualValue() === $updatedState->getExpectedValue()) {
			$updatedState = $this->manager->update(
				$updatedState,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_KEY => null,
					States\Property::PENDING_KEY => null,
				]),
			);
		}

		$this->dispatcher?->dispatch(new Events\ConnectorPropertyStateEntityUpdated($property, $state, $updatedState));

		return $updatedState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function delete(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
	): bool
	{
		if ($this->manager === null || $this->repository === null) {
			throw new Exceptions\NotImplemented('Connector properties state manager is not registered');
		}

		$state = $this->repository->findOne($property);

		if ($state === null) {
			return true;
		}

		$result = $this->manager->delete($state);

		$this->dispatcher?->dispatch(new Events\ConnectorPropertyStateEntityDeleted($property));

		return $result;
	}

}
