<?php declare(strict_types = 1);

/**
 * ChannelPropertiesManager.php
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
use function property_exists;

/**
 * Channel property states manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		protected readonly IChannelPropertiesManager|null $manager = null,
		protected readonly IChannelPropertiesRepository|null $repository = null,
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty $property,
		Utils\ArrayHash $values,
	): States\ChannelProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Channel properties state manager is not registered');
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

		$this->dispatcher?->dispatch(new Events\ChannelPropertyStateEntityCreated($property, $createdState));

		return $createdState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function update(
		MetadataDocuments\DevicesModule\ChannelDynamicProperty $property,
		States\ChannelProperty $state,
		Utils\ArrayHash $values,
	): States\ChannelProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Channel properties state manager is not registered');
		}

		$updatedState = $this->manager->update($state, $values);

		if ($updatedState->getActualValue() === $updatedState->getExpectedValue()) {
			$updatedState = $this->manager->update(
				$updatedState,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_KEY => null,
					States\Property::PENDING_KEY => false,
				]),
			);
		}

		$this->dispatcher?->dispatch(new Events\ChannelPropertyStateEntityUpdated($property, $state, $updatedState));

		return $updatedState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function delete(
		MetadataDocuments\DevicesModule\ChannelDynamicProperty $property,
	): bool
	{
		if ($this->manager === null || $this->repository === null) {
			throw new Exceptions\NotImplemented('Channel properties state manager is not registered');
		}

		$state = $this->repository->findOne($property);

		if ($state === null) {
			return true;
		}

		$result = $this->manager->delete($state);

		$this->dispatcher?->dispatch(new Events\ChannelPropertyStateEntityDeleted($property));

		return $result;
	}

}
