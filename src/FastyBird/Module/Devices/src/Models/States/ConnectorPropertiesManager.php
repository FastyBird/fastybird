<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.32.0
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
use function strval;

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
		protected readonly IConnectorPropertiesManager|null $manager,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher,
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
			property_exists($values, 'actualValue')
			&& property_exists($values, 'expectedValue')
		) {
			if (strval($values->offsetGet('actualValue')) === strval($values->offsetGet('expectedValue'))) {
				$values->offsetSet('expectedValue', null);
				$values->offsetSet('pending', null);
			}
		}

		$createdState = $this->manager->create($property->getId(), $values);

		$this->dispatcher?->dispatch(new Events\StateEntityCreated($property, $createdState));

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

		if (strval($updatedState->getActualValue()) === strval($updatedState->getExpectedValue())) {
			$updatedState = $this->manager->update(
				$updatedState,
				Utils\ArrayHash::from([
					'expectedValue' => null,
					'pending' => null,
				]),
			);
		}

		$this->dispatcher?->dispatch(new Events\StateEntityUpdated($property, $state, $updatedState));

		return $updatedState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function delete(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		States\ConnectorProperty $state,
	): bool
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Connector properties state manager is not registered');
		}

		$result = $this->manager->delete($state);

		$this->dispatcher?->dispatch(new Events\StateEntityDeleted($property));

		return $result;
	}

}
