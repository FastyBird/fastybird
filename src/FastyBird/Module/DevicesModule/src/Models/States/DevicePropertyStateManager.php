<?php declare(strict_types = 1);

/**
 * DevicePropertyStateManager.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     DynamicProperties
 * @since          0.73.0
 *
 * @date           23.08.22
 */

namespace FastyBird\DevicesModule\Models\States;

use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Models;
use FastyBird\Metadata;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use Nette;
use Nette\Utils;
use Psr\Log;
use function is_array;

/**
 * Useful device dynamic property state helpers
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     DynamicProperties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertyStateManager
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Models\States\DevicePropertiesRepository $devicePropertyStateRepository,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		Log\LoggerInterface|null $logger,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function setValue(
		MetadataEntities\DevicesModule\DeviceDynamicProperty $property,
		Utils\ArrayHash $data,
	): void
	{
		try {
			$propertyState = $this->devicePropertyStateRepository->findOne($property);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'DynamicProperties repository is not configured. State could not be fetched',
				[
					'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
					'type' => 'device-property-state-manager',
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
						'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
						'type' => 'device-property-state-manager',
						'device' => [
							'id' => $property->getDevice()->toString(),
						],
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
						'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
						'type' => 'device-property-state-manager',
						'device' => [
							'id' => $property->getDevice()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
							'state' => $propertyState->toArray(),
						],
					],
				);
			}
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'DynamicProperties manager is not configured. State could not be saved',
				[
					'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
					'type' => 'device-property-state-manager',
				],
			);
		}
	}

	/**
	 * @param MetadataEntities\DevicesModule\DeviceDynamicProperty|Array<MetadataEntities\DevicesModule\DeviceDynamicProperty> $property
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function setValidState(
		MetadataEntities\DevicesModule\DeviceDynamicProperty|array $property,
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
