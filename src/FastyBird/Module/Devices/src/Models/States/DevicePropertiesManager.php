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
 * @date           23.08.22
 */

namespace FastyBird\Module\Devices\Models\States;

use DateTimeInterface;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Utilities;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use function assert;
use function is_array;
use function strval;

/**
 * Useful device dynamic property state helpers
 *
 * @extends PropertiesManager<MetadataDocuments\DevicesModule\DeviceDynamicProperty, MetadataDocuments\DevicesModule\DeviceMappedProperty | null, States\DeviceProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertiesManager extends PropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\States\Devices\Repository $devicePropertyStateRepository,
		private readonly Models\States\Devices\Manager $devicePropertiesStatesManager,
		private readonly Devices\Logger $logger,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		ObjectMapper\Processing\Processor $stateMapper,
	)
	{
		parent::__construct($stateMapper);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function read(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
	): States\DeviceProperty|null
	{
		return $this->loadValue($property, true);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function get(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
	): States\DeviceProperty|null
	{
		return $this->loadValue($property, false);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function write(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
	): void
	{
		$this->saveValue($property, $data, true);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function set(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
	): void
	{
		$this->saveValue($property, $data, false);
	}

	/**
	 * @param MetadataDocuments\DevicesModule\DeviceDynamicProperty|array<MetadataDocuments\DevicesModule\DeviceDynamicProperty>|Entities\Devices\Properties\Dynamic|array<Entities\Devices\Properties\Dynamic> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setValidState(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|Entities\Devices\Properties\Dynamic|array $property,
		bool $state,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->set($item, Utils\ArrayHash::from([
					States\Property::VALID_FIELD => $state,
				]));
			}
		} else {
			$this->set($property, Utils\ArrayHash::from([
				States\Property::VALID_FIELD => $state,
			]));
		}
	}

	/**
	 * @param MetadataDocuments\DevicesModule\DeviceDynamicProperty|array<MetadataDocuments\DevicesModule\DeviceDynamicProperty>|Entities\Devices\Properties\Dynamic|array<Entities\Devices\Properties\Dynamic> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setPendingState(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|Entities\Devices\Properties\Dynamic|array $property,
		bool $pending,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				if ($pending === false) {
					$this->set($item, Utils\ArrayHash::from([
						States\Property::EXPECTED_VALUE_FIELD => null,
						States\Property::PENDING_FIELD => false,
					]));
				} else {
					$this->set($item, Utils\ArrayHash::from([
						States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(
							DateTimeInterface::ATOM,
						),
					]));
				}
			}
		} else {
			if ($pending === false) {
				$this->set($property, Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]));
			} else {
				$this->set($property, Utils\ArrayHash::from([
					States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(DateTimeInterface::ATOM),
				]));
			}
		}
	}

	public function delete(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty $property,
	): void
	{
		try {
			$this->devicePropertiesStatesManager->delete($property);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'device-properties-states',
				],
			);
		}
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function loadValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		bool $forReading,
	): States\DeviceProperty|null
	{
		if ($property instanceof Entities\Devices\Properties\Property) {
			$property = $this->devicePropertiesConfigurationRepository->find($property->getId());
			assert(
				$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
				|| $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty,
			);
		}

		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		try {
			$state = $this->devicePropertyStateRepository->find($property->getId());

			if ($state === null) {
				return null;
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
								'type' => 'device-properties-states',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
							],
						);

					} else {
						$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
							States\Property::ACTUAL_VALUE_FIELD => null,
							States\Property::VALID_FIELD => false,
						]));

						$this->logger->error(
							'Property stored actual value was not valid',
							[
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'type' => 'device-properties-states',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
							],
						);

						return $this->loadValue($property, $forReading);
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
						$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_FIELD => null,
							States\Property::PENDING_FIELD => false,
						]));

						$this->logger->warning(
							'Property is not settable but has stored expected value',
							[
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'type' => 'device-properties-states',
							],
						);

						return $this->loadValue($mappedProperty ?? $property, $forReading);
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
								'type' => 'device-properties-states',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
							],
						);

					} else {
						$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_FIELD => null,
							States\Property::PENDING_FIELD => false,
						]));

						$this->logger->error(
							'Property stored expected value was not valid',
							[
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'type' => 'device-properties-states',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
							],
						);

						return $this->loadValue($property, $forReading);
					}
				}
			}

			if ($updateValues === []) {
				return $state;
			}

			return $this->updateState($state, $state::class, $updateValues);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'device-properties-states',
				],
			);
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function saveValue(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
		bool $forWriting,
	): void
	{
		if ($property instanceof Entities\Devices\Properties\Property) {
			$property = $this->devicePropertiesConfigurationRepository->find($property->getId());
			assert(
				$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
				|| $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty,
			);
		}

		$mappedProperty = null;

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		if ($mappedProperty !== null && $forWriting === false) {
			throw new Exceptions\InvalidArgument('Mapped property could not be stored as from device');
		}

		$state = $this->loadValue($mappedProperty ?? $property, $forWriting);

		if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
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
					$data->offsetSet(States\Property::VALID_FIELD, true);
				}
			} catch (MetadataExceptions\InvalidValue $ex) {
				$data->offsetUnset(States\Property::ACTUAL_VALUE_FIELD);
				$data->offsetSet(States\Property::VALID_FIELD, false);

				$this->logger->error(
					'Provided property actual value is not valid',
					[
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'type' => 'device-properties-states',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);
			}
		}

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
						throw new Exceptions\InvalidArgument(
							'Property is not settable, expected value could not written',
						);
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
							'type' => 'device-properties-states',
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
			return;
		}

		if (
			$state !== null
			&& MetadataUtilities\Value::flattenValue($state->getActualValue()) === $data->offsetGet(
				States\Property::EXPECTED_VALUE_FIELD,
			)
		) {
			$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
			$data->offsetSet(States\Property::PENDING_FIELD, false);
		}

		try {
			$result = $state === null ? $this->devicePropertiesStatesManager->create(
				$property,
				$data,
			) : $this->devicePropertiesStatesManager->update(
				$property,
				$state,
				$data,
			);

			$this->logger->debug(
				$state === null ? 'Device property state was created' : 'Device property state was updated',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'device-properties-states',
					'property' => [
						'id' => $property->getId()->toString(),
						'state' => $result->toArray(),
					],
				],
			);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'device-properties-states',
				],
			);
		}
	}

}
