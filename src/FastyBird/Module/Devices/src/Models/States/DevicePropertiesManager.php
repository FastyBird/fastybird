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

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use function array_diff;
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
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function create(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty $property,
		Utils\ArrayHash $values,
	): States\DeviceProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Device properties state manager is not registered');
		}

		if (
			property_exists($values, States\Property::ACTUAL_VALUE_FIELD)
			&& property_exists($values, States\Property::EXPECTED_VALUE_FIELD)
			&& $values->offsetGet(States\Property::ACTUAL_VALUE_FIELD) === $values->offsetGet(
				States\Property::EXPECTED_VALUE_FIELD,
			)
		) {
			$values->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
			$values->offsetSet(States\Property::PENDING_FIELD, null);
		}

		$createdState = $this->manager->create($property->getId(), $values);

		$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityCreated($property, $createdState));

		return $createdState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function update(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty $property,
		States\DeviceProperty $state,
		Utils\ArrayHash $values,
	): States\DeviceProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Device properties state manager is not registered');
		}

		$updatedState = $this->manager->update($state, $values);

		if ($updatedState->getActualValue() === $updatedState->getExpectedValue()) {
			$updatedState = $this->manager->update(
				$updatedState,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]),
			);
		}

		if (
			array_diff(
				[
					$state->getActualValue(),
					$state->getExpectedValue(),
					$state->getPending(),
					$state->isValid(),
				],
				[
					$updatedState->getActualValue(),
					$updatedState->getExpectedValue(),
					$updatedState->getPending(),
					$updatedState->isValid(),
				],
			) !== []
		) {
			$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityUpdated($property, $state, $updatedState));
		}

		return $updatedState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function delete(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty $property,
	): bool
	{
		if ($this->manager === null || $this->repository === null) {
			throw new Exceptions\NotImplemented('Device properties state manager is not registered');
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
