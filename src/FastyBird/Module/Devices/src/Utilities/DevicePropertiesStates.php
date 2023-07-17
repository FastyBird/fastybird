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
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Psr\Log;
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

	public function __construct(
		private readonly Models\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly Models\States\DevicePropertiesRepository $devicePropertyStateRepository,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function readValue(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
	): States\DeviceProperty|null
	{
		return $this->loadValue($property, true);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getValue(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
	): States\DeviceProperty|null
	{
		return $this->loadValue($property, false);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeValue(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
	): void
	{
		$this->saveValue($property, $data, true);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function setValue(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
	): void
	{
		$this->saveValue($property, $data, false);
	}

	/**
	 * @param MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|array<MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty>|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped|array<Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped> $property
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function setValidState(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped|array $property,
		bool $state,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->writeValue($item, Utils\ArrayHash::from([
					States\Property::VALID_KEY => $state,
				]));
			}
		} else {
			$this->writeValue($property, Utils\ArrayHash::from([
				States\Property::VALID_KEY => $state,
			]));
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function loadValue(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		bool $forReading,
	): States\DeviceProperty|null
	{
		if ($property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty) {
			if ($property->getParent() === null) {
				throw new Exceptions\InvalidState('Parent identifier for mapped property is missing');
			}

			$findDevicePropertyQuery = new Queries\FindDeviceProperties();
			$findDevicePropertyQuery->byId($property->getParent());

			$parent = $this->devicePropertiesRepository->findOneBy($findDevicePropertyQuery);

			if (!$parent instanceof Entities\Devices\Properties\Dynamic) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$property = $parent;
		} elseif ($property instanceof Entities\Devices\Properties\Mapped) {
			$property = $property->getParent();

			if (!$property instanceof Entities\Devices\Properties\Dynamic) {
				throw new Exceptions\InvalidState('Mapped property parent is invalid type');
			}
		}

		try {
			$state = $this->devicePropertyStateRepository->findOne($property);

			if ($state !== null) {
				try {
					if ($state->getActualValue() !== null) {
						if ($forReading) {
							$state->setActualValue(
								ValueHelper::normalizeReadValue(
									$property->getDataType(),
									$state->getActualValue(),
									$property->getFormat(),
									$property->getScale(),
									$property->getInvalid(),
								),
							);

						} else {
							$state->setActualValue(
								ValueHelper::normalizeValue(
									$property->getDataType(),
									$state->getActualValue(),
									$property->getFormat(),
									$property->getInvalid(),
								),
							);
						}
					}
				} catch (Exceptions\InvalidState $ex) {
					$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
						States\Property::ACTUAL_VALUE_KEY => null,
						States\Property::VALID_KEY => false,
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
						if ($forReading) {
							$state->setExpectedValue(
								ValueHelper::normalizeReadValue(
									$property->getDataType(),
									$state->getExpectedValue(),
									$property->getFormat(),
									$property->getScale(),
									$property->getInvalid(),
								),
							);

						} else {
							$state->setExpectedValue(
								ValueHelper::normalizeValue(
									$property->getDataType(),
									$state->getExpectedValue(),
									$property->getFormat(),
									$property->getInvalid(),
								),
							);
						}
					}
				} catch (Exceptions\InvalidState $ex) {
					$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
						States\Property::EXPECTED_VALUE_KEY => null,
						States\Property::PENDING_KEY => false,
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
			}

			return $state;
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
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function saveValue(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
		bool $forWriting,
	): void
	{
		if ($property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty) {
			if ($property->getParent() === null) {
				throw new Exceptions\InvalidState('Parent identifier for mapped property is missing');
			}

			$findPropertyQuery = new Queries\FindDeviceProperties();
			$findPropertyQuery->byId($property->getParent());

			$parent = $this->devicePropertiesRepository->findOneBy($findPropertyQuery);

			if (!$parent instanceof Entities\Devices\Properties\Dynamic) {
				throw new Exceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$property = $parent;
		} elseif ($property instanceof Entities\Devices\Properties\Mapped) {
			$property = $property->getParent();

			if (!$property instanceof Entities\Devices\Properties\Dynamic) {
				throw new Exceptions\InvalidState('Mapped property parent is invalid type');
			}
		}

		$state = $this->loadValue($property, $forWriting);

		if ($data->offsetExists(States\Property::ACTUAL_VALUE_KEY)) {
			if ($forWriting) {
				try {
					$data->offsetSet(
						States\Property::ACTUAL_VALUE_KEY,
						ValueHelper::flattenValue(
							ValueHelper::normalizeWriteValue(
								$property->getDataType(),
								/** @phpstan-ignore-next-line */
								$data->offsetGet(States\Property::ACTUAL_VALUE_KEY),
								$property->getFormat(),
								$property->getScale(),
								$property->getInvalid(),
							),
						),
					);
				} catch (Exceptions\InvalidState $ex) {
					$data->offsetSet(States\Property::ACTUAL_VALUE_KEY, null);
					$data->offsetSet(States\Property::VALID_KEY, false);

					$this->logger->error(
						'Provided property actual value was not valid',
						[
							'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
							'type' => 'device-properties-states',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);
				}
			} else {
				try {
					$data->offsetSet(
						States\Property::ACTUAL_VALUE_KEY,
						ValueHelper::flattenValue(
							ValueHelper::normalizeValue(
								$property->getDataType(),
								/** @phpstan-ignore-next-line */
								$data->offsetGet(States\Property::ACTUAL_VALUE_KEY),
								$property->getFormat(),
								$property->getInvalid(),
							),
						),
					);
				} catch (Exceptions\InvalidState $ex) {
					$data->offsetSet(States\Property::ACTUAL_VALUE_KEY, null);
					$data->offsetSet(States\Property::VALID_KEY, false);

					$this->logger->error(
						'Provided property actual value was not valid',
						[
							'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
							'type' => 'device-properties-states',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);
				}
			}
		}

		if ($data->offsetExists(States\Property::EXPECTED_VALUE_KEY)) {
			if ($forWriting) {
				try {
					$data->offsetSet(
						States\Property::EXPECTED_VALUE_KEY,
						ValueHelper::flattenValue(
							ValueHelper::normalizeWriteValue(
								$property->getDataType(),
								/** @phpstan-ignore-next-line */
								$data->offsetGet(States\Property::EXPECTED_VALUE_KEY),
								$property->getFormat(),
								$property->getScale(),
								$property->getInvalid(),
							),
						),
					);
				} catch (Exceptions\InvalidState $ex) {
					$data->offsetSet(States\Property::EXPECTED_VALUE_KEY, null);
					$data->offsetSet(States\Property::PENDING_KEY, false);

					$this->logger->error(
						'Provided property expected value was not valid',
						[
							'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
							'type' => 'device-properties-states',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);
				}
			} else {
				try {
					$data->offsetSet(
						States\Property::EXPECTED_VALUE_KEY,
						ValueHelper::flattenValue(
							ValueHelper::normalizeValue(
								$property->getDataType(),
								/** @phpstan-ignore-next-line */
								$data->offsetGet(States\Property::EXPECTED_VALUE_KEY),
								$property->getFormat(),
								$property->getInvalid(),
							),
						),
					);
				} catch (Exceptions\InvalidState $ex) {
					$data->offsetSet(States\Property::EXPECTED_VALUE_KEY, null);
					$data->offsetSet(States\Property::PENDING_KEY, false);

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

}
