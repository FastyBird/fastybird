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

namespace FastyBird\DevicesModule\Models\States;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Events;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\States;
use FastyBird\DevicesModule\Utilities;
use FastyBird\Metadata\Entities as MetadataEntities;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
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

	public function create(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|MetadataEntities\DevicesModule\ConnectorMappedProperty|Entities\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $values,
	): States\ConnectorProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Connector properties state manager is not registered');
		}

		if (
			$values->offsetExists('actualValue')
			&& $values->offsetExists('expectedValue')
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

		$createdState = $this->manager->create($property, $values);

		$this->dispatcher?->dispatch(new Events\StateEntityCreated($property, $createdState));

		return $createdState;
	}

	public function update(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|MetadataEntities\DevicesModule\ConnectorMappedProperty|Entities\Connectors\Properties\Dynamic $property,
		States\ConnectorProperty $state,
		Utils\ArrayHash $values,
	): States\ConnectorProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Connector properties state manager is not registered');
		}

		$updatedState = $this->manager->update($property, $state, $values);

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
				$property,
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

	public function delete(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|MetadataEntities\DevicesModule\ConnectorMappedProperty|Entities\Connectors\Properties\Dynamic $property,
		States\ConnectorProperty $state,
	): bool
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Connector properties state manager is not registered');
		}

		$result = $this->manager->delete($property, $state);

		$this->dispatcher?->dispatch(new Events\StateEntityDeleted($property));

		return $result;
	}

}
