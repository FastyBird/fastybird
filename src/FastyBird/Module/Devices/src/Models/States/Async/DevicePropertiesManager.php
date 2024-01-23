<?php declare(strict_types = 1);

/**
 * DevicePropertiesStates.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           22.01.24
 */

namespace FastyBird\Module\Devices\Models\States\Async;

use DateTimeInterface;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Utilities;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use React\Promise;
use Throwable;
use function boolval;
use function is_array;
use function React\Async\async;
use function React\Async\await;
use function strval;

/**
 * Useful device dynamic property state helpers
 *
 * @extends Models\States\PropertiesManager<MetadataDocuments\DevicesModule\DeviceDynamicProperty, MetadataDocuments\DevicesModule\DeviceMappedProperty | null, States\DeviceProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertiesManager extends Models\States\PropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\States\Devices\Async\Repository $devicePropertyStateRepository,
		private readonly Models\States\Devices\Async\Manager $devicePropertiesStatesManager,
		private readonly Devices\Logger $logger,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		ObjectMapper\Processing\Processor $stateMapper,
	)
	{
		parent::__construct($stateMapper);
	}

	/**
	 * @return Promise\PromiseInterface<States\DeviceProperty|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function read(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
	): Promise\PromiseInterface
	{
		return $this->loadValue($property, true);
	}

	/**
	 * @return Promise\PromiseInterface<States\DeviceProperty|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function get(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
	): Promise\PromiseInterface
	{
		return $this->loadValue($property, false);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function write(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		Utils\ArrayHash $data,
	): Promise\PromiseInterface
	{
		return $this->saveValue($property, $data, true);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function set(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		Utils\ArrayHash $data,
	): Promise\PromiseInterface
	{
		return $this->saveValue($property, $data, false);
	}

	/**
	 * @param MetadataDocuments\DevicesModule\DeviceDynamicProperty|array<MetadataDocuments\DevicesModule\DeviceDynamicProperty> $property
	 *
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function setValidState(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|array $property,
		bool $state,
	): Promise\PromiseInterface
	{
		if (is_array($property)) {
			$deferred = new Promise\Deferred();

			$promises = [];

			foreach ($property as $item) {
				$promises[] = $this->set($item, Utils\ArrayHash::from([
					States\Property::VALID_FIELD => $state,
				]));
			}

			Promise\all($promises)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->set($property, Utils\ArrayHash::from([
			States\Property::VALID_FIELD => $state,
		]));
	}

	/**
	 * @param MetadataDocuments\DevicesModule\DeviceDynamicProperty|array<MetadataDocuments\DevicesModule\DeviceDynamicProperty> $property
	 *
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function setPendingState(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|array $property,
		bool $pending,
	): Promise\PromiseInterface
	{
		if (is_array($property)) {
			$deferred = new Promise\Deferred();

			$promises = [];

			foreach ($property as $item) {
				$promises[] = $pending === false ? $this->set($item, Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				])) : $this->set($item, Utils\ArrayHash::from([
					States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(
						DateTimeInterface::ATOM,
					),
				]));
			}

			Promise\all($promises)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $pending === false ? $this->set($property, Utils\ArrayHash::from([
			States\Property::EXPECTED_VALUE_FIELD => null,
			States\Property::PENDING_FIELD => false,
		])) : $this->set($property, Utils\ArrayHash::from([
			States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(DateTimeInterface::ATOM),
		]));
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function delete(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		try {
			return $this->devicePropertiesStatesManager->delete($id);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states manager is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'async-device-properties-states',
				],
			);
		}

		return Promise\resolve(false);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function normalizePublishValue(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|null
	{
		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		if ($mappedProperty !== null) {
			if (
				!Utilities\Value::compareDataTypes(
					$mappedProperty->getDataType(),
					$property->getDataType(),
				)
			) {
				throw new Exceptions\InvalidState(
					'Mapped property data type is not compatible with dynamic property data type',
				);
			}
		}

		return MetadataUtilities\Value::transformDataType(
			MetadataUtilities\Value::flattenValue($value),
			$mappedProperty?->getDataType() ?? $property->getDataType(),
		);
	}

	/**
	 * @return Promise\PromiseInterface<States\DeviceProperty|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function loadValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		bool $forReading,
	): Promise\PromiseInterface
	{
		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				return Promise\reject(new Exceptions\InvalidState('Mapped property parent could not be loaded'));
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		$deferred = new Promise\Deferred();

		$this->devicePropertyStateRepository->find($property->getId())
			->then(
				function (
					States\DeviceProperty|null $state,
				) use (
					$deferred,
					$property,
					$mappedProperty,
					$forReading,
				): void {
					if ($state === null) {
						$deferred->resolve(null);

						return;
					}

					$updateValues = [];

					if ($mappedProperty !== null) {
						$updateValues['id'] = $mappedProperty->getId();
					}

					if ($state->getActualValue() !== null) {
						try {
							$updateValues[States\Property::ACTUAL_VALUE_FIELD] = $this->convertReadValue(
								$state->getActualValue(),
								$property,
								$mappedProperty,
								$forReading,
							);
						} catch (MetadataExceptions\InvalidValue $ex) {
							if ($mappedProperty !== null) {
								$updateValues[States\Property::ACTUAL_VALUE_FIELD] = null;
								$updateValues[States\Property::VALID_FIELD] = false;

								$this->logger->error(
									'Property stored actual value could not be converted to mapped property',
									[
										'source' => MetadataTypes\ModuleSource::DEVICES,
										'type' => 'async-device-properties-states',
										'exception' => ApplicationHelpers\Logger::buildException($ex),
									],
								);

							} else {
								$this->devicePropertiesStatesManager->update(
									$property,
									$state,
									Utils\ArrayHash::from([
										States\Property::ACTUAL_VALUE_FIELD => null,
										States\Property::VALID_FIELD => false,
									]),
								)
									->then(async(function () use ($deferred, $property, $forReading): void {
										$deferred->resolve(await($this->loadValue($property, $forReading)));
									}))
									->catch(static function (Throwable $ex) use ($deferred): void {
										$deferred->reject($ex);
									})
									->finally(function () use ($ex): void {
										$this->logger->error(
											'Property stored actual value was not valid',
											[
												'source' => MetadataTypes\ModuleSource::DEVICES,
												'type' => 'async-device-properties-states',
												'exception' => ApplicationHelpers\Logger::buildException($ex),
											],
										);
									});

								return;
							}
						}
					}

					if ($state->getExpectedValue() !== null) {
						try {
							$expectedValue = $this->convertReadValue(
								$state->getExpectedValue(),
								$property,
								$mappedProperty,
								$forReading,
							);

							if ($expectedValue !== null && !$property->isSettable()) {
								$this->devicePropertiesStatesManager->update(
									$property,
									$state,
									Utils\ArrayHash::from([
										States\Property::EXPECTED_VALUE_FIELD => null,
										States\Property::PENDING_FIELD => false,
									]),
								)
									->then(async(
										function () use ($deferred, $property, $mappedProperty, $forReading): void {
											$deferred->resolve(await(
												$this->loadValue($mappedProperty ?? $property, $forReading),
											));
										},
									))
									->catch(static function (Throwable $ex) use ($deferred): void {
										$deferred->reject($ex);
									})
									->finally(function (): void {
										$this->logger->warning(
											'Property is not settable but has stored expected value',
											[
												'source' => MetadataTypes\ModuleSource::DEVICES,
												'type' => 'async-device-properties-states',
											],
										);
									});

								return;
							}

							$updateValues[States\Property::EXPECTED_VALUE_FIELD] = $expectedValue;
						} catch (MetadataExceptions\InvalidValue $ex) {
							if ($mappedProperty !== null) {
								$updateValues[States\Property::EXPECTED_VALUE_FIELD] = null;
								$updateValues[States\Property::PENDING_FIELD] = false;

								$this->logger->error(
									'Property stored actual value could not be converted to mapped property',
									[
										'source' => MetadataTypes\ModuleSource::DEVICES,
										'type' => 'async-device-properties-states',
										'exception' => ApplicationHelpers\Logger::buildException($ex),
									],
								);

							} else {
								$this->devicePropertiesStatesManager->update(
									$property,
									$state,
									Utils\ArrayHash::from([
										States\Property::EXPECTED_VALUE_FIELD => null,
										States\Property::PENDING_FIELD => false,
									]),
								)
									->then(async(function () use ($deferred, $property, $forReading): void {
										$deferred->resolve(await($this->loadValue($property, $forReading)));
									}))
									->catch(static function (Throwable $ex) use ($deferred): void {
										$deferred->reject($ex);
									})
									->finally(function () use ($ex): void {
										$this->logger->error(
											'Property stored expected value was not valid',
											[
												'source' => MetadataTypes\ModuleSource::DEVICES,
												'type' => 'async-device-properties-states',
												'exception' => ApplicationHelpers\Logger::buildException($ex),
											],
										);
									});

								return;
							}
						}
					}

					if ($updateValues === []) {
						$deferred->resolve($state);

						return;
					}

					$deferred->resolve($this->updateState($state, $state::class, $updateValues));
				},
			)
			->catch(function (Throwable $ex) use ($deferred): void {
				if ($ex instanceof Exceptions\NotImplemented) {
					$this->logger->warning(
						'Devices states repository is not configured. State could not be fetched',
						[
							'source' => MetadataTypes\ModuleSource::DEVICES,
							'type' => 'async-device-properties-states',
						],
					);
				}

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function saveValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty $property,
		Utils\ArrayHash $data,
		bool $forWriting,
	): Promise\PromiseInterface
	{
		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				return Promise\reject(new Exceptions\InvalidState('Mapped property parent could not be loaded'));
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		$deferred = new Promise\Deferred();

		$this->devicePropertyStateRepository->find($property->getId())
			->then(async(
				function (
					States\DeviceProperty|null $state,
				) use (
					$deferred,
					$data,
					$property,
					$mappedProperty,
					$forWriting,
				): void {
					/**
					 * IMPORTANT: ACTUAL VALUE field is meant to be used only by connectors for saving device actual value
					 */
					if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
						if ($mappedProperty !== null) {
							$deferred->reject(new Exceptions\InvalidArgument(
								'Setting property actual value is not allowed for mapped properties',
							));

							return;
						}

						if ($forWriting === true) {
							$deferred->reject(new Exceptions\InvalidArgument(
								'Setting property actual value could be done only by "setValue" method',
							));

							return;
						}

						try {
							if (
								$property->getInvalid() !== null
								&& strval(
									MetadataUtilities\Value::flattenValue(
									/** @phpstan-ignore-next-line */
										$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
									),
								) === strval(
									MetadataUtilities\Value::flattenValue($property->getInvalid()),
								)
							) {
								$data->offsetSet(States\Property::ACTUAL_VALUE_FIELD, null);
								$data->offsetSet(States\Property::VALID_FIELD, false);

							} else {
								$actualValue = $this->convertWriteActualValue(
								/** @phpstan-ignore-next-line */
									$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
									$property,
								);

								$data->offsetSet(
									States\Property::ACTUAL_VALUE_FIELD,
									MetadataUtilities\Value::flattenValue($actualValue),
								);

								if ($data->offsetExists(States\Property::VALID_FIELD)) {
									$data->offsetSet(
										States\Property::VALID_FIELD,
										boolval($data->offsetGet(States\Property::VALID_FIELD)),
									);
								} else {
									$data->offsetSet(States\Property::VALID_FIELD, true);
								}
							}
						} catch (MetadataExceptions\InvalidValue $ex) {
							$data->offsetUnset(States\Property::ACTUAL_VALUE_FIELD);
							$data->offsetSet(States\Property::VALID_FIELD, false);

							$this->logger->error(
								'Provided property actual value is not valid',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'async-device-properties-states',
									'exception' => ApplicationHelpers\Logger::buildException($ex),
								],
							);
						}
					}

					/**
					 * IMPORTANT: EXPECTED VALUE field is meant to be used mainly by user interface for saving value which should
					 * be then written into device
					 */
					if ($data->offsetExists(States\Property::EXPECTED_VALUE_FIELD)) {
						if (
							$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD) !== null
							&& $data->offsetGet(States\Property::EXPECTED_VALUE_FIELD) !== ''
						) {
							try {
								$expectedValue = $this->convertWriteExpectedValue(
								/** @phpstan-ignore-next-line */
									$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD),
									$property,
									$mappedProperty,
									$forWriting,
								);

								if (
									$expectedValue !== null
									&& (
										!$property->isSettable()
										|| (
											$mappedProperty !== null
											&& !$mappedProperty->isSettable()
										)
									)
								) {
									$deferred->reject(new Exceptions\InvalidArgument(
										'Property is not settable, expected value could not written',
									));

									return;
								}

								$data->offsetSet(
									States\Property::EXPECTED_VALUE_FIELD,
									MetadataUtilities\Value::flattenValue($expectedValue),
								);
								$data->offsetSet(
									States\Property::PENDING_FIELD,
									$expectedValue !== null,
								);
							} catch (MetadataExceptions\InvalidValue $ex) {
								$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
								$data->offsetSet(States\Property::PENDING_FIELD, false);

								$this->logger->error(
									'Provided property expected value was not valid',
									[
										'source' => MetadataTypes\ModuleSource::DEVICES,
										'type' => 'async-device-properties-states',
										'exception' => ApplicationHelpers\Logger::buildException($ex),
									],
								);
							}
						} else {
							$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
							$data->offsetSet(States\Property::PENDING_FIELD, false);
						}
					}

					if ($data->count() === 0) {
						$deferred->resolve(true);

						return;
					}

					if (
						$state !== null
						&& (
							(
								$data->offsetExists(States\Property::EXPECTED_VALUE_FIELD)
								&& MetadataUtilities\Value::flattenValue($state->getActualValue()) === $data->offsetGet(
									States\Property::EXPECTED_VALUE_FIELD,
								)
							) || (
								$data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)
								&& MetadataUtilities\Value::flattenValue(
									$state->getExpectedValue(),
								) === $data->offsetGet(
									States\Property::ACTUAL_VALUE_FIELD,
								)
							)
						)
					) {
						$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
						$data->offsetSet(States\Property::PENDING_FIELD, false);
					}

					try {
						$result = await($state === null ? $this->devicePropertiesStatesManager->create(
							$property,
							$data,
						) : $this->devicePropertiesStatesManager->update(
							$property,
							$state,
							$data,
						));

						$this->logger->debug(
							$state === null ? 'Device property state was created' : 'Device property state was updated',
							[
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'type' => 'async-device-properties-states',
								'property' => [
									'id' => $property->getId()->toString(),
									'state' => $result->toArray(),
								],
							],
						);

						$deferred->resolve(true);
					} catch (Throwable $ex) {
						if ($ex instanceof Exceptions\NotImplemented) {
							$this->logger->warning(
								'Devices states manager is not configured. State could not be saved',
								[
									'source' => MetadataTypes\ModuleSource::DEVICES,
									'type' => 'async-device-properties-states',
								],
							);
						}

						$deferred->reject($ex);
					}
				},
			))
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

}
