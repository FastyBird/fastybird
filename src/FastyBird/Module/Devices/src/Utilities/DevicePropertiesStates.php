<?php declare(strict_types = 1);

/**
 * DevicePropertiesStates.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          0.73.0
 *
 * @date           23.08.22
 */

namespace FastyBird\Module\Devices\Utilities;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
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

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Models\DataStorage\DevicePropertiesRepository $devicePropertiesRepository,
		private readonly Models\States\DevicePropertiesRepository $devicePropertyStateRepository,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		Log\LoggerInterface|null $logger,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function setValue(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
	): void
	{
		if ($property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty) {
			if ($property->getParent() === null) {
				throw new Exceptions\InvalidState('Parent identifier for mapped property is missing');
			}

			$parent = $this->devicePropertiesRepository->findById($property->getParent());

			if (!$parent instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty) {
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
			$propertyState = $this->devicePropertyStateRepository->findOneById($property->getId());
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'device-properties-states',
				],
			);

			return;
		}

		try {
			// In case synchronization failed...
			if ($propertyState === null) {
				// ...create state in storage
				$propertyState = $this->devicePropertiesStatesManager->create(
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
							'state' => $propertyState->toArray(),
						],
					],
				);
			} else {
				$propertyState = $this->devicePropertiesStatesManager->update(
					$property,
					$propertyState,
					$data,
				);

				$this->logger->debug(
					'Device property state was updated',
					[
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'type' => 'device-properties-states',
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $propertyState->toArray(),
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
	 * @param MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|Array<MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty>|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped|Array<Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped> $property
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function setValidState(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|Entities\Devices\Properties\Dynamic|Entities\Devices\Properties\Mapped|array $property,
		bool $state,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->setValue($item, Utils\ArrayHash::from([
					'valid' => $state,
				]));
			}
		} else {
			$this->setValue($property, Utils\ArrayHash::from([
				'valid' => $state,
			]));
		}
	}

}
