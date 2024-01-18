<?php declare(strict_types = 1);

/**
 * Manager.php
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

namespace FastyBird\Module\Devices\Models\States\Channels;

use DateTimeInterface;
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
final class Manager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly IManager|null $manager = null,
		private readonly IRepository|null $repository = null,
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

		$updatedState = $this->manager->update($property->getId(), $values);

		if ($updatedState === false) {
			return $state;
		}

		if ($updatedState->getActualValue() === $updatedState->getExpectedValue()) {
			$updatedState = $this->manager->update(
				$property->getId(),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]),
			);

			if ($updatedState === false) {
				return $state;
			}
		}

		if (
			[
				States\Property::ACTUAL_VALUE_FIELD => $state->getActualValue(),
				States\Property::EXPECTED_VALUE_FIELD => $state->getExpectedValue(),
				States\Property::PENDING_FIELD => $state->getPending() instanceof DateTimeInterface
					? $state->getPending()->format(DateTimeInterface::ATOM)
					: $state->getPending(),
				States\Property::VALID_FIELD => $state->isValid(),
			] !== [
				States\Property::ACTUAL_VALUE_FIELD => $updatedState->getActualValue(),
				States\Property::EXPECTED_VALUE_FIELD => $updatedState->getExpectedValue(),
				States\Property::PENDING_FIELD => $updatedState->getPending() instanceof DateTimeInterface
					? $updatedState->getPending()->format(DateTimeInterface::ATOM)
					: $updatedState->getPending(),
				States\Property::VALID_FIELD => $updatedState->isValid(),
			]
		) {
			$this->dispatcher?->dispatch(
				new Events\ChannelPropertyStateEntityUpdated($property, $state, $updatedState),
			);
		}

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

		$result = $this->manager->delete($property->getId());

		$this->dispatcher?->dispatch(new Events\ChannelPropertyStateEntityDeleted($property));

		return $result;
	}

}
