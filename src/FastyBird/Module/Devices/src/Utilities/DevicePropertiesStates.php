<?php declare(strict_types = 1);

/**
 * DevicePropertiesStates.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           23.08.22
 */

namespace FastyBird\Module\Devices\Utilities;

use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities\ValueHelper;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use function array_merge;
use function assert;
use function is_array;

/**
 * Useful device dynamic property state helpers
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertiesStates
{

	use Nette\SmartObject;

	/**
	 * @param Models\Configuration\Devices\Properties\Repository<MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty> $devicePropertiesRepository
	 */
	public function __construct(
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesRepository,
		private readonly Models\States\DevicePropertiesRepository $devicePropertyStateRepository,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Devices\Logger $logger,
		private readonly ObjectMapper\Processing\Processor $stateMapper,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function readValue(
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
	 */
	public function getValue(
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
	 */
	public function writeValue(
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
	 */
	public function setValue(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
	): void
	{
		$this->saveValue($property, $data, false);
	}

	/**
	 * @param MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|array<MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty>|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped|array<Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function setValidState(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped|array $property,
		bool $state,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->setValue($item, Utils\ArrayHash::from([
					States\Property::VALID_FIELD => $state,
				]));
			}
		} else {
			$this->setValue($property, Utils\ArrayHash::from([
				States\Property::VALID_FIELD => $state,
			]));
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function loadValue(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		bool $forReading,
	): States\DeviceProperty|null
	{
		if ($property instanceof Entities\Devices\Properties\Property) {
			$findPropertyQuery = new Queries\Configuration\FindDeviceProperties();
			$findPropertyQuery->byId($property->getId());

			$property = $this->devicePropertiesRepository->findOneBy($findPropertyQuery);
			assert(
				$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
				|| $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty,
			);
		}

		$mapped = null;

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$findPropertyQuery = new Queries\Configuration\FindDeviceProperties();
			$findPropertyQuery->byId($property->getParent());

			$parent = $this->devicePropertiesRepository->findOneBy($findPropertyQuery);

			if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mapped = $property;

			$property = $parent;
		}

		try {
			$state = $this->devicePropertyStateRepository->findOne($property);

			if ($state === null) {
				return null;
			}

			$updateValues = [];

			if ($mapped !== null) {
				$updateValues['id'] = $mapped->getId();
			}

			try {
				if ($state->getActualValue() !== null) {
					$actualValue = $forReading ? ValueHelper::normalizeReadValue(
						$property->getDataType(),
						$state->getActualValue(),
						$property->getFormat(),
						$property->getScale(),
						$property->getInvalid(),
					) : ValueHelper::normalizeValue(
						$property->getDataType(),
						$state->getActualValue(),
						$property->getFormat(),
						$property->getInvalid(),
					);

					$updateValues[States\Property::ACTUAL_VALUE_FIELD] = $mapped !== null
						? ValueHelper::transformValueFromMappedParent(
							$mapped->getDataType(),
							$property->getDataType(),
							$actualValue,
						)
						: $actualValue;
				}
			} catch (Exceptions\InvalidArgument $ex) {
				$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => null,
					States\Property::VALID_FIELD => false,
				]));

				$this->logger->error(
					'Property stored actual value was not valid',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'device-properties-states',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);

				return $this->loadValue($property, $forReading);
			}

			try {
				if ($state->getExpectedValue() !== null) {
					$expectedValue = $forReading ? ValueHelper::normalizeReadValue(
						$property->getDataType(),
						$state->getExpectedValue(),
						$property->getFormat(),
						$property->getScale(),
						$property->getInvalid(),
					) : ValueHelper::normalizeValue(
						$property->getDataType(),
						$state->getExpectedValue(),
						$property->getFormat(),
						$property->getInvalid(),
					);

					$updateValues[States\Property::EXPECTED_VALUE_FIELD] = $mapped !== null
						? ValueHelper::transformValueFromMappedParent(
							$mapped->getDataType(),
							$property->getDataType(),
							$expectedValue,
						)
						: $expectedValue;
				}
			} catch (Exceptions\InvalidArgument $ex) {
				$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]));

				$this->logger->error(
					'Property stored expected value was not valid',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'device-properties-states',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);

				return $this->loadValue($property, $forReading);
			}

			if ($updateValues === []) {
				return $state;
			}

			return $this->updateState($state, $updateValues);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
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
	 */
	private function saveValue(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
		bool $forWriting,
	): void
	{
		if ($property instanceof Entities\Devices\Properties\Property) {
			$findPropertyQuery = new Queries\Configuration\FindDeviceProperties();
			$findPropertyQuery->byId($property->getId());

			$property = $this->devicePropertiesRepository->findOneBy($findPropertyQuery);
			assert(
				$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
				|| $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty,
			);
		}

		$mapped = null;

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty) {
			$findPropertyQuery = new Queries\Configuration\FindDeviceProperties();
			$findPropertyQuery->byId($property->getParent());

			$parent = $this->devicePropertiesRepository->findOneBy($findPropertyQuery);

			if (!$parent instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mapped = $property;

			$property = $parent;
		}

		$state = $this->loadValue($property, $forWriting);

		if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
			if ($forWriting) {
				$actualValue = $mapped !== null
					? ValueHelper::normalizeWriteValue(
						$mapped->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
						$mapped->getFormat(),
						$mapped->getScale(),
						$mapped->getInvalid(),
					)
					: ValueHelper::normalizeWriteValue(
						$property->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
						$property->getFormat(),
						$property->getScale(),
						$property->getInvalid(),
					);

			} else {
				$actualValue = $mapped !== null
					? ValueHelper::normalizeValue(
						$mapped->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
						$mapped->getFormat(),
						$mapped->getInvalid(),
					)
					: ValueHelper::normalizeValue(
						$property->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
						$property->getFormat(),
						$property->getInvalid(),
					);
			}

			if ($mapped !== null) {
				$actualValue = ValueHelper::transformValueToMappedParent(
					$mapped->getDataType(),
					$property->getDataType(),
					$actualValue,
				);
			}

			try {
				$data->offsetSet(
					States\Property::ACTUAL_VALUE_FIELD,
					ValueHelper::flattenValue($actualValue),
				);
			} catch (Exceptions\InvalidArgument $ex) {
				$data->offsetSet(States\Property::ACTUAL_VALUE_FIELD, null);
				$data->offsetSet(States\Property::VALID_FIELD, false);

				$this->logger->error(
					'Provided property actual value is not valid',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'device-properties-states',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);
			}
		}

		if ($data->offsetExists(States\Property::EXPECTED_VALUE_FIELD)) {
			if ($forWriting) {
				$expectedValue = $mapped !== null
					? ValueHelper::normalizeWriteValue(
						$mapped->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD),
						$mapped->getFormat(),
						$mapped->getScale(),
						$mapped->getInvalid(),
					)
					: ValueHelper::normalizeWriteValue(
						$property->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD),
						$property->getFormat(),
						$property->getScale(),
						$property->getInvalid(),
					);

			} else {
				$expectedValue = $mapped !== null
					? ValueHelper::normalizeValue(
						$mapped->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD),
						$mapped->getFormat(),
						$mapped->getInvalid(),
					)
					: ValueHelper::normalizeValue(
						$property->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD),
						$property->getFormat(),
						$property->getInvalid(),
					);
			}

			if ($mapped !== null) {
				$expectedValue = ValueHelper::transformValueToMappedParent(
					$mapped->getDataType(),
					$property->getDataType(),
					$expectedValue,
				);
			}

			try {
				$data->offsetSet(
					States\Property::EXPECTED_VALUE_FIELD,
					ValueHelper::flattenValue($expectedValue),
				);
			} catch (Exceptions\InvalidArgument $ex) {
				$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
				$data->offsetSet(States\Property::PENDING_FIELD, false);

				$this->logger->error(
					'Provided property expected value was not valid',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'device-properties-states',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);
			}
		}

		try {
			// In case synchronization failed...
			if ($state === null) {
				// ...create state in storage
				$state = $this->devicePropertiesStatesManager->create(
					$property,
					$data,
				);

				$this->logger->debug(
					'Device property state was created',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'device-properties-states',
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $state->toArray(),
						],
					],
				);
			} else {
				$state = $this->devicePropertiesStatesManager->update(
					$property,
					$state,
					$data,
				);

				$this->logger->debug(
					'Device property state was updated',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'device-properties-states',
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $state->toArray(),
						],
					],
				);
			}
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'device-properties-states',
				],
			);
		}
	}

	/**
	 * @param array<string, mixed> $update
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function updateState(
		States\DeviceProperty $state,
		array $update,
	): States\DeviceProperty
	{
		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->stateMapper->process(
				array_merge(
					$state->toArray(),
					[
						$state::ACTUAL_VALUE_FIELD => $state->getActualValue(),
						$state::EXPECTED_VALUE_FIELD => $state->getExpectedValue(),
						$state::PENDING_FIELD => $state->getPending(),
						$state::VALID_FIELD => $state->isValid(),
					],
					$update,
				),
				$state::class,
				$options,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\InvalidArgument('Could not map data to state: ' . $errorPrinter->printError($ex));
		}
	}

}
