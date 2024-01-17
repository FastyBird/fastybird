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
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use function assert;
use function is_array;
use function strval;

/**
 * Useful connector dynamic property state helpers
 *
 * @extends PropertiesStates<MetadataDocuments\DevicesModule\ConnectorDynamicProperty, null, States\ConnectorProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorPropertiesStates extends PropertiesStates
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesRepository $connectorPropertyStateRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
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
	 * @param MetadataDocuments\DevicesModule\ConnectorDynamicProperty|array<MetadataDocuments\DevicesModule\ConnectorDynamicProperty>|Entities\Connectors\Properties\Dynamic|array<Entities\Connectors\Properties\Dynamic> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function setPendingState(
		MetadataDocuments\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic|array $property,
		bool $pending,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				if ($pending === false) {
					$this->setValue($item, Utils\ArrayHash::from([
						States\Property::EXPECTED_VALUE_FIELD => null,
						States\Property::PENDING_FIELD => false,
					]));
				} else {
					$this->setValue($item, Utils\ArrayHash::from([
						States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(
							DateTimeInterface::ATOM,
						),
					]));
				}
			}
		} else {
			if ($pending === false) {
				$this->setValue($property, Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]));
			} else {
				$this->setValue($property, Utils\ArrayHash::from([
					States\Property::PENDING_FIELD => $this->dateTimeFactory->getNow()->format(DateTimeInterface::ATOM),
				]));
			}
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

			if ($state->getActualValue() !== null) {
				try {
					$updateValues[States\Property::ACTUAL_VALUE_FIELD] = $this->convertReadValue(
						$state->getActualValue(),
						$property,
						null,
						$forReading,
					);
				} catch (MetadataExceptions\InvalidValue $ex) {
					$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
						States\Property::ACTUAL_VALUE_FIELD => null,
						States\Property::VALID_FIELD => false,
					]));

					$this->logger->error(
						'Property stored actual value was not valid',
						[
							'source' => MetadataTypes\ModuleSource::DEVICES,
							'type' => 'connector-properties-states',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);

					return $this->loadValue($property, $forReading);
				}
			}

			if ($state->getExpectedValue() !== null) {
				try {
					$expectedValue = $this->convertReadValue(
						$state->getExpectedValue(),
						$property,
						null,
						$forReading,
					);

					if ($expectedValue !== null && !$property->isSettable()) {
						$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_FIELD => null,
							States\Property::PENDING_FIELD => false,
						]));

						$this->logger->warning(
							'Property is not settable but has stored expected value',
							[
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'type' => 'connector-properties-states',
							],
						);

						return $this->loadValue($property, $forReading);
					}

					$updateValues[States\Property::EXPECTED_VALUE_FIELD] = $expectedValue;
				} catch (MetadataExceptions\InvalidValue $ex) {
					$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
						States\Property::EXPECTED_VALUE_FIELD => null,
						States\Property::PENDING_FIELD => false,
					]));

					$this->logger->error(
						'Property stored expected value was not valid',
						[
							'source' => MetadataTypes\ModuleSource::DEVICES,
							'type' => 'connector-properties-states',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);

					return $this->loadValue($property, $forReading);
				}
			}

			if ($updateValues === []) {
				return $state;
			}

			return $this->updateState($state, $state::class, $updateValues);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
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
						'type' => 'connector-properties-states',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
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
						null,
						$forWriting,
					);

					if ($expectedValue !== null && !$property->isSettable()) {
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
							'type' => 'connector-properties-states',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
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
						'source' => MetadataTypes\ModuleSource::DEVICES,
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
						'source' => MetadataTypes\ModuleSource::DEVICES,
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
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'connector-properties-states',
				],
			);
		}
	}

}
