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

namespace FastyBird\Module\Devices\Models\States\Devices;

use DateTimeInterface;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\Promise;
use function property_exists;

/**
 * Device property states manager
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
	 * @return States\DeviceProperty|Promise\PromiseInterface<States\DeviceProperty>
	 *
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function create(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty $property,
		Utils\ArrayHash $values,
	): States\DeviceProperty|Promise\PromiseInterface
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

		$result = $this->manager->create($property->getId(), $values);

		if ($result instanceof Promise\PromiseInterface) {
			throw new Exceptions\NotImplemented('Promise base manager is not implemented');
		}

		$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityCreated($property, $result));

		return $result;
	}

	/**
	 * @return States\DeviceProperty|Promise\PromiseInterface<States\DeviceProperty|false>
	 *
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function update(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty $property,
		States\DeviceProperty $state,
		Utils\ArrayHash $values,
	): States\DeviceProperty|Promise\PromiseInterface
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Device properties state manager is not registered');
		}

		$result = $this->manager->update($property->getId(), $values);

		if ($result instanceof Promise\PromiseInterface) {
			throw new Exceptions\NotImplemented('Promise base manager is not implemented');
		}

		if ($result === false) {
			return $state;
		}

		if ($result->getActualValue() === $result->getExpectedValue()) {
			$result = $this->manager->update(
				$property->getId(),
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]),
			);

			if ($result instanceof Promise\PromiseInterface) {
				throw new Exceptions\NotImplemented('Promise base manager is not implemented');
			}

			if ($result === false) {
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
				States\Property::ACTUAL_VALUE_FIELD => $result->getActualValue(),
				States\Property::EXPECTED_VALUE_FIELD => $result->getExpectedValue(),
				States\Property::PENDING_FIELD => $result->getPending() instanceof DateTimeInterface
					? $result->getPending()->format(DateTimeInterface::ATOM)
					: $result->getPending(),
				States\Property::VALID_FIELD => $result->isValid(),
			]
		) {
			$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityUpdated($property, $state, $result));
		}

		return $result;
	}

	/**
	 * @return Promise\PromiseInterface<bool>|bool
	 *
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function delete(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty $property,
	): Promise\PromiseInterface|bool
	{
		if ($this->manager === null || $this->repository === null) {
			throw new Exceptions\NotImplemented('Device properties state manager is not registered');
		}

		$state = $this->repository->findOne($property);

		if ($state === null) {
			return true;
		}

		$result = $this->manager->delete($property->getId());

		if ($result instanceof Promise\PromiseInterface) {
			return $result;
		}

		$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityDeleted($property));

		return $result;
	}

}
