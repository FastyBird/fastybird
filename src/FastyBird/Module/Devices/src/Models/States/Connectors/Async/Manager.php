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

namespace FastyBird\Module\Devices\Models\States\Connectors\Async;

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
use Throwable;

/**
 * Asynchronous connector property states manager
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
		private readonly Models\States\Connectors\Manager $fallback,
		private readonly IManager|null $manager = null,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<States\ConnectorProperty>
	 *
	 * @interal
	 */
	public function create(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
		Utils\ArrayHash $values,
	): Promise\PromiseInterface
	{
		if ($this->manager === null) {
			try {
				return Promise\resolve($this->fallback->create($property, $values));
			} catch (Exceptions\NotImplemented $ex) {
				return Promise\reject($ex);
			}
		}

		if (
			$values->offsetExists(States\Property::ACTUAL_VALUE_FIELD)
			&& $values->offsetExists(States\Property::EXPECTED_VALUE_FIELD)
			&& $values->offsetGet(States\Property::ACTUAL_VALUE_FIELD) === $values->offsetGet(
				States\Property::EXPECTED_VALUE_FIELD,
			)
		) {
			$values->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
			$values->offsetSet(States\Property::PENDING_FIELD, null);
		}

		$deferred = new Promise\Deferred();

		$this->manager->create($property->getId(), $values)
			->then(function (States\ConnectorProperty $result) use ($deferred, $property): void {
				$this->dispatcher?->dispatch(new Events\ConnectorPropertyStateEntityCreated($property, $result));

				$deferred->resolve($result);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<States\ConnectorProperty|false>
	 *
	 * @interal
	 */
	public function update(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
		States\ConnectorProperty $state,
		Utils\ArrayHash $values,
	): Promise\PromiseInterface
	{
		if ($this->manager === null) {
			try {
				return Promise\resolve($this->fallback->update($property, $state, $values));
			} catch (Exceptions\NotImplemented $ex) {
				return Promise\reject($ex);
			}
		}

		if (
			$values->offsetExists(States\Property::ACTUAL_VALUE_FIELD)
			&& $values->offsetExists(States\Property::EXPECTED_VALUE_FIELD)
			&& $values->offsetGet(States\Property::ACTUAL_VALUE_FIELD) === $values->offsetGet(
				States\Property::EXPECTED_VALUE_FIELD,
			)
		) {
			$values->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
			$values->offsetSet(States\Property::PENDING_FIELD, null);
		}

		$deferred = new Promise\Deferred();

		$this->manager->update($property->getId(), $values)
			->then(function (States\ConnectorProperty|false $result) use ($deferred, $property, $state): void {
				if ($result === false) {
					$deferred->resolve($state);

					return;
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
					$this->dispatcher?->dispatch(
						new Events\ConnectorPropertyStateEntityUpdated($property, $state, $result),
					);
				}

				$deferred->resolve($result);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @interal
	 */
	public function delete(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty $property,
	): Promise\PromiseInterface
	{
		if ($this->manager === null) {
			try {
				return Promise\resolve($this->fallback->delete($property));
			} catch (Exceptions\NotImplemented $ex) {
				return Promise\reject($ex);
			}
		}

		$deferred = new Promise\Deferred();

		$this->manager->delete($property->getId())
			->then(function (bool $result) use ($deferred, $property): void {
				$this->dispatcher?->dispatch(new Events\ConnectorPropertyStateEntityDeleted($property));

				$deferred->resolve($result);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

}
