<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesStates.php
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

use DateTimeInterface;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use function array_merge;
use function assert;
use function is_array;

/**
 * Useful connector dynamic property state helpers
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorPropertiesStates
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesRepository $connectorPropertyStateRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
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
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
	): States\ConnectorProperty|null
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
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
	): States\ConnectorProperty|null
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
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
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
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
	): void
	{
		$this->saveValue($property, $data, false);
	}

	/**
	 * @param MetadataDocuments\DevicesModule\ConnectorDynamicProperty|array<MetadataDocuments\DevicesModule\ConnectorDynamicProperty>|Entities\Connectors\Properties\Dynamic|array<Entities\Connectors\Properties\Dynamic> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function setValidState(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic|array $property,
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
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		bool $forReading,
	): States\ConnectorProperty|null
	{
		if ($property instanceof Entities\Connectors\Properties\Property) {
			$property = $this->connectorPropertiesConfigurationRepository->find($property->getId());
			assert($property instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty);
		}

		try {
			$state = $this->connectorPropertyStateRepository->findOne($property);

			if ($state === null) {
				return null;
			}

			$updateValues = [];

			try {
				if ($state->getActualValue() !== null) {
					$actualValue = MetadataUtilities\ValueHelper::normalizeValue(
						$property->getDataType(),
						$state->getActualValue(),
						$property->getFormat(),
					);

					if ($forReading) {
						$actualValue = MetadataUtilities\ValueHelper::transformReadValue(
							$property->getDataType(),
							$actualValue,
							$property->getValueTransformer() instanceof MetadataValueObjects\EquationTransformer
								? $property->getValueTransformer()
								: null,
							$property->getScale(),
						);
					}

					$updateValues[States\Property::ACTUAL_VALUE_FIELD] = $actualValue;
				}
			} catch (Exceptions\InvalidArgument $ex) {
				$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => null,
					States\Property::VALID_FIELD => false,
				]));

				$this->logger->error(
					'Property stored actual value was not valid',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'connector-properties-states',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);

				return $this->loadValue($property, $forReading);
			}

			try {
				if ($state->getExpectedValue() !== null) {
					$expectedValue = MetadataUtilities\ValueHelper::normalizeValue(
						$property->getDataType(),
						$state->getExpectedValue(),
						$property->getFormat(),
					);

					if ($forReading) {
						$expectedValue = MetadataUtilities\ValueHelper::transformReadValue(
							$property->getDataType(),
							$expectedValue,
							$property->getValueTransformer() instanceof MetadataValueObjects\EquationTransformer
								? $property->getValueTransformer()
								: null,
							$property->getScale(),
						);
					}

					$updateValues[States\Property::EXPECTED_VALUE_FIELD] = $expectedValue;
				}
			} catch (Exceptions\InvalidArgument $ex) {
				$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]));

				$this->logger->error(
					'Property stored expected value was not valid',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'connector-properties-states',
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
				'Connectors states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'connector-properties-states',
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
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
		bool $forWriting,
	): void
	{
		if ($property instanceof Entities\Connectors\Properties\Property) {
			$property = $this->connectorPropertiesConfigurationRepository->find($property->getId());
			assert($property instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty);
		}

		$state = $this->loadValue($property, $forWriting);

		if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
			$actualValue = MetadataUtilities\ValueHelper::normalizeValue(
				$property->getDataType(),
				/** @phpstan-ignore-next-line */
					$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
				$property->getFormat(),
			);

			if ($forWriting) {
				$actualValue = MetadataUtilities\ValueHelper::transformWriteValue(
					$property->getDataType(),
					$actualValue,
					$property->getValueTransformer() instanceof MetadataValueObjects\EquationTransformer
						? $property->getValueTransformer()
						: null,
					$property->getScale(),
				);
			}

			try {
				$data->offsetSet(
					States\Property::ACTUAL_VALUE_FIELD,
					MetadataUtilities\ValueHelper::flattenValue($actualValue),
				);
			} catch (Exceptions\InvalidArgument $ex) {
				$data->offsetSet(States\Property::ACTUAL_VALUE_FIELD, null);
				$data->offsetSet(States\Property::VALID_FIELD, false);

				$this->logger->error(
					'Provided property actual value is not valid',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'connector-properties-states',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);
			}
		}

		if ($data->offsetExists(States\Property::EXPECTED_VALUE_FIELD)) {
			$expectedValue = MetadataUtilities\ValueHelper::normalizeValue(
				$property->getDataType(),
				/** @phpstan-ignore-next-line */
					$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD),
				$property->getFormat(),
			);

			if ($forWriting) {
				$expectedValue = MetadataUtilities\ValueHelper::transformWriteValue(
					$property->getDataType(),
					$expectedValue,
					$property->getValueTransformer() instanceof MetadataValueObjects\EquationTransformer
						? $property->getValueTransformer()
						: null,
					$property->getScale(),
				);
			}

			try {
				$data->offsetSet(
					States\Property::EXPECTED_VALUE_FIELD,
					MetadataUtilities\ValueHelper::flattenValue($expectedValue),
				);
			} catch (Exceptions\InvalidArgument $ex) {
				$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
				$data->offsetSet(States\Property::PENDING_FIELD, false);

				$this->logger->error(
					'Provided property expected value was not valid',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'connector-properties-states',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);
			}
		}

		try {
			// In case synchronization failed...
			if ($state === null) {
				// ...create state in storage
				$state = $this->connectorPropertiesStatesManager->create(
					$property,
					$data,
				);

				$this->logger->debug(
					'Connector property state was created',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'connector-properties-states',
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $state->toArray(),
						],
					],
				);
			} else {
				$state = $this->connectorPropertiesStatesManager->update(
					$property,
					$state,
					$data,
				);

				$this->logger->debug(
					'Connector property state was updated',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'connector-properties-states',
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $state->toArray(),
						],
					],
				);
			}
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'connector-properties-states',
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
		States\ConnectorProperty $state,
		array $update,
	): States\ConnectorProperty
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
						$state::CREATED_AT => $state->getCreatedAt()?->format(DateTimeInterface::ATOM),
						$state::UPDATED_AT => $state->getUpdatedAt()?->format(DateTimeInterface::ATOM),
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
