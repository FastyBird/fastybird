<?php declare(strict_types = 1);

/**
 * DevicePropertiesManager.php
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
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Utilities;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use function property_exists;
use function strval;

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
		protected readonly IDevicePropertiesManager|null $manager,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\NotImplemented
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
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
			property_exists($values, 'actualValue')
			&& property_exists($values, 'expectedValue')
		) {
			$actualValue = Utilities\ValueHelper::normalizeValue(
				$property->getDataType(),
				strval($values->offsetGet('actualValue')),
				$property->getFormat(),
				$property->getInvalid(),
			);

			$expectedValue = Utilities\ValueHelper::normalizeValue(
				$property->getDataType(),
				strval($values->offsetGet('expectedValue')),
				$property->getFormat(),
				$property->getInvalid(),
			);

			if ($expectedValue === $actualValue) {
				$values->offsetSet('expectedValue', null);
				$values->offsetSet('pending', null);
			}
		}

		$createdState = $this->manager->create($property->getId(), $values);

		$this->dispatcher?->dispatch(new Events\StateEntityCreated($property, $createdState));

		return $createdState;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\NotImplemented
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
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

		$actualValue = Utilities\ValueHelper::normalizeValue(
			$property->getDataType(),
			$updatedState->getActualValue(),
			$property->getFormat(),
			$property->getInvalid(),
		);

		$expectedValue = Utilities\ValueHelper::normalizeValue(
			$property->getDataType(),
			$updatedState->getExpectedValue(),
			$property->getFormat(),
			$property->getInvalid(),
		);

		if ($expectedValue === $actualValue) {
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
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function delete(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|Entities\Devices\Properties\Dynamic $property,
		States\DeviceProperty $state,
	): bool
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Device properties state manager is not registered');
		}

		if ($property->getParent() !== null) {
			throw new Exceptions\InvalidState('Child property can\'t have state');
		}

		$result = $this->manager->delete($state);

		$this->dispatcher?->dispatch(new Events\StateEntityDeleted($property));

		return $result;
	}

}
