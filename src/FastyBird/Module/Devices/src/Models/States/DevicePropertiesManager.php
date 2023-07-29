<?php declare(strict_types = 1);

/**
 * DevicePropertiesManager.php
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
 * Device property states manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		protected readonly IDevicePropertiesManager|null $manager = null,
		protected readonly IDevicePropertiesRepository|null $repository = null,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function create(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|Entities\Devices\Properties\Dynamic $property,
		Utils\ArrayHash $values,
	): States\DeviceProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Device properties state manager is not registered');
		}

		if ($property->getParent() !== null) {
			throw new Exceptions\InvalidState('Child property can\'t have state');
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

		$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityCreated($property, $createdState));

		return $createdState;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function update(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|Entities\Devices\Properties\Dynamic $property,
		States\DeviceProperty $state,
		Utils\ArrayHash $values,
	): States\DeviceProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Device properties state manager is not registered');
		}

		if ($property->getParent() !== null) {
			throw new Exceptions\InvalidState('Child property can\'t have state');
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

		$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityUpdated($property, $state, $updatedState));

		return $updatedState;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function delete(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|Entities\Devices\Properties\Dynamic $property,
	): bool
	{
		if ($this->manager === null || $this->repository === null) {
			throw new Exceptions\NotImplemented('Device properties state manager is not registered');
		}

		if ($property->getParent() !== null) {
			throw new Exceptions\InvalidState('Child property can\'t have state');
		}

		$state = $this->repository->findOne($property);

		if ($state === null) {
			return true;
		}

		$result = $this->manager->delete($state);

		$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityDeleted($property));

		return $result;
	}

}
