<?php declare(strict_types = 1);

/**
 * ChannelPropertiesStates.php
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
 * Useful channel dynamic property state helpers
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertiesStates
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\ChannelPropertiesRepository $channelPropertyStateRepository,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
	): States\ChannelProperty|null
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
	): States\ChannelProperty|null
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|Entities\Channels\Properties\Dynamic $property,
		Utils\ArrayHash $data,
	): void
	{
		$this->saveValue($property, $data, false);
	}

	/**
	 * @param MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|array<MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty>|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped|array<Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function setValidState(
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped|array $property,
		bool $state,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->writeValue($item, Utils\ArrayHash::from([
					States\Property::VALID_FIELD => $state,
				]));
			}
		} else {
			$this->writeValue($property, Utils\ArrayHash::from([
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
		bool $forReading,
	): States\ChannelProperty|null
	{
		if ($property instanceof Entities\Channels\Properties\Property) {
			$property = $this->channelPropertiesConfigurationRepository->find($property->getId());
			assert(
				$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
				|| $property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty,
			);
		}

		$mapped = null;

		if ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
			$parent = $this->channelPropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mapped = $property;

			$property = $parent;
		}

		if ($mapped !== null && $forReading === false) {
			throw new Exceptions\InvalidArgument('Mapped property could not be read as to device');
		}

		try {
			$state = $this->channelPropertyStateRepository->findOne($property);

			if ($state === null) {
				return null;
			}

			$updateValues = [];

			if ($mapped !== null) {
				$updateValues['id'] = $mapped->getId();
			}

			if ($state->getActualValue() !== null) {
				try {
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

						if ($mapped !== null) {
							$actualValue = MetadataUtilities\ValueHelper::transformValueFromMappedParent(
								$mapped->getDataType(),
								$property->getDataType(),
								$actualValue,
							);

							$actualValue = MetadataUtilities\ValueHelper::transformReadValue(
								$mapped->getDataType(),
								$actualValue,
								$mapped->getValueTransformer() instanceof MetadataValueObjects\EquationTransformer
									? $mapped->getValueTransformer()
									: null,
								$mapped->getScale(),
							);
						}
					}

					$updateValues[States\Property::ACTUAL_VALUE_FIELD] = $actualValue;
				} catch (Exceptions\InvalidArgument | MetadataExceptions\InvalidValue $ex) {
					$this->channelPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
						States\Property::ACTUAL_VALUE_FIELD => null,
						States\Property::VALID_FIELD => false,
					]));

					$this->logger->error(
						'Property stored actual value was not valid',
						[
							'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
							'type' => 'channel-properties-states',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);

					return $this->loadValue($property, $forReading);
				}
			}

			if ($state->getExpectedValue() !== null) {
				try {
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

						if ($mapped !== null) {
							$expectedValue = MetadataUtilities\ValueHelper::transformValueFromMappedParent(
								$mapped->getDataType(),
								$property->getDataType(),
								$expectedValue,
							);

							$expectedValue = MetadataUtilities\ValueHelper::transformReadValue(
								$mapped->getDataType(),
								$expectedValue,
								$mapped->getValueTransformer() instanceof MetadataValueObjects\EquationTransformer
									? $mapped->getValueTransformer()
									: null,
								$mapped->getScale(),
							);
						}
					}

					$expectedValue = $forReading
						? $expectedValue
						: MetadataUtilities\ValueHelper::transformValueToDevice(
							$property->getDataType(),
							$property->getFormat(),
							$expectedValue,
						);

					$updateValues[States\Property::EXPECTED_VALUE_FIELD] = $expectedValue;
				} catch (Exceptions\InvalidArgument | MetadataExceptions\InvalidValue $ex) {
					$this->channelPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
						States\Property::EXPECTED_VALUE_FIELD => null,
						States\Property::PENDING_FIELD => false,
					]));

					$this->logger->error(
						'Property stored expected value was not valid',
						[
							'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
							'type' => 'channel-properties-states',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);

					return $this->loadValue($property, $forReading);
				}
			}

			if ($updateValues === []) {
				return $state;
			}

			return $this->updateState($state, $updateValues);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Channels states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'channel-properties-states',
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
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
		Utils\ArrayHash $data,
		bool $forWriting,
	): void
	{
		if ($property instanceof Entities\Channels\Properties\Property) {
			$property = $this->channelPropertiesConfigurationRepository->find($property->getId());
			assert(
				$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
				|| $property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty,
			);
		}

		$mapped = null;

		if ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
			$parent = $this->channelPropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mapped = $property;

			$property = $parent;
		}

		if ($mapped !== null && $forWriting === false) {
			throw new Exceptions\InvalidArgument('Mapped property could not be stored as from device');
		}

		$state = $this->loadValue($property, $forWriting);

		if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
			try {
				$actualValue = $forWriting
					? $data->offsetGet(States\Property::ACTUAL_VALUE_FIELD)
					: MetadataUtilities\ValueHelper::transformValueFromDevice(
						$property->getDataType(),
						$property->getFormat(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
					);

				$actualValue = $mapped !== null
					? MetadataUtilities\ValueHelper::normalizeValue(
						$mapped->getDataType(),
						/** @phpstan-ignore-next-line */
						$actualValue,
						$mapped->getFormat(),
					)
					: MetadataUtilities\ValueHelper::normalizeValue(
						$property->getDataType(),
						/** @phpstan-ignore-next-line */
						$actualValue,
						$property->getFormat(),
					);

				if ($forWriting) {
					if ($mapped !== null) {
						$actualValue = MetadataUtilities\ValueHelper::transformWriteValue(
							$mapped->getDataType(),
							$actualValue,
							$mapped->getValueTransformer() instanceof MetadataValueObjects\EquationTransformer
								? $mapped->getValueTransformer()
								: null,
							$property->getScale(),
						);

						$actualValue = MetadataUtilities\ValueHelper::transformValueToMappedParent(
							$mapped->getDataType(),
							$property->getDataType(),
							$actualValue,
						);
					}

					$actualValue = MetadataUtilities\ValueHelper::transformWriteValue(
						$property->getDataType(),
						$actualValue,
						$property->getValueTransformer() instanceof MetadataValueObjects\EquationTransformer
							? $property->getValueTransformer()
							: null,
						$property->getScale(),
					);
				}

				$data->offsetSet(
					States\Property::ACTUAL_VALUE_FIELD,
					MetadataUtilities\ValueHelper::flattenValue($actualValue),
				);
			} catch (Exceptions\InvalidArgument | MetadataExceptions\InvalidValue $ex) {
				$data->offsetSet(States\Property::ACTUAL_VALUE_FIELD, null);
				$data->offsetSet(States\Property::VALID_FIELD, false);

				$this->logger->error(
					'Provided property actual value is not valid',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'channel-properties-states',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);
			}
		}

		if ($data->offsetExists(States\Property::EXPECTED_VALUE_FIELD)) {
			try {
				$expectedValue = $mapped !== null
					? MetadataUtilities\ValueHelper::normalizeValue(
						$mapped->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD),
						$mapped->getFormat(),
					)
					: MetadataUtilities\ValueHelper::normalizeValue(
						$property->getDataType(),
						/** @phpstan-ignore-next-line */
						$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD),
						$property->getFormat(),
					);

				if ($forWriting) {
					if ($mapped !== null) {
						$expectedValue = MetadataUtilities\ValueHelper::transformWriteValue(
							$mapped->getDataType(),
							$expectedValue,
							$mapped->getValueTransformer() instanceof MetadataValueObjects\EquationTransformer
								? $mapped->getValueTransformer()
								: null,
							$property->getScale(),
						);

						$expectedValue = MetadataUtilities\ValueHelper::transformValueToMappedParent(
							$mapped->getDataType(),
							$property->getDataType(),
							$expectedValue,
						);
					}

					$expectedValue = MetadataUtilities\ValueHelper::transformWriteValue(
						$property->getDataType(),
						$expectedValue,
						$property->getValueTransformer() instanceof MetadataValueObjects\EquationTransformer
							? $property->getValueTransformer()
							: null,
						$property->getScale(),
					);
				}

				$data->offsetSet(
					States\Property::EXPECTED_VALUE_FIELD,
					MetadataUtilities\ValueHelper::flattenValue($expectedValue),
				);
			} catch (Exceptions\InvalidArgument | MetadataExceptions\InvalidValue $ex) {
				$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
				$data->offsetSet(States\Property::PENDING_FIELD, false);

				$this->logger->error(
					'Provided property expected value was not valid',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'channel-properties-states',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);
			}
		}

		try {
			// In case synchronization failed...
			if ($state === null) {
				// ...create state in storage
				$state = $this->channelPropertiesStatesManager->create(
					$property,
					$data,
				);

				$this->logger->debug(
					'Channel property state was created',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'channel-properties-states',
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $state->toArray(),
						],
					],
				);
			} else {
				$state = $this->channelPropertiesStatesManager->update(
					$property,
					$state,
					$data,
				);

				$this->logger->debug(
					'Channel property state was updated',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'channel-properties-states',
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $state->toArray(),
						],
					],
				);
			}
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Channels states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'channel-properties-states',
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
		States\ChannelProperty $state,
		array $update,
	): States\ChannelProperty
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
