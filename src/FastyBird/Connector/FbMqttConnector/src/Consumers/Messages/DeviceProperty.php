<?php declare(strict_types = 1);

/**
 * DeviceProperty.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 * @since          0.4.0
 *
 * @date           05.02.22
 */

namespace FastyBird\FbMqttConnector\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use FastyBird\DevicesModule\Utilities as DevicesModuleUtilities;
use FastyBird\FbMqttConnector;
use FastyBird\FbMqttConnector\Consumers;
use FastyBird\FbMqttConnector\Entities;
use FastyBird\FbMqttConnector\Helpers;
use FastyBird\Metadata;
use FastyBird\Metadata\Entities as MetadataEntities;
use Nette;
use Nette\Utils;
use Psr\Log;
use Throwable;
use function array_merge;
use function assert;
use function count;
use function sprintf;

/**
 * Device property MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceProperty implements Consumers\Consumer
{

	use Nette\SmartObject;
	use TProperty;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModuleModels\Devices\DevicesRepository $deviceRepository,
		private readonly DevicesModuleModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModuleModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModuleModels\DataStorage\DevicesRepository $devicesDataStorageRepository,
		private readonly DevicesModuleModels\DataStorage\DevicePropertiesRepository $propertiesDataStorageRepository,
		private readonly DevicesModuleModels\States\DevicePropertiesRepository $propertyStateRepository,
		private readonly DevicesModuleModels\States\DevicePropertiesManager $propertiesStatesManager,
		private readonly Helpers\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Metadata\Exceptions\FileNotFound
	 * @throws Throwable
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceProperty) {
			return false;
		}

		if ($entity->getValue() !== FbMqttConnector\Constants::VALUE_NOT_SET) {
			$deviceItem = $this->devicesDataStorageRepository->findByIdentifier(
				$entity->getConnector(),
				$entity->getDevice(),
			);

			if ($deviceItem === null) {
				$this->logger->error(
					sprintf('Device "%s" is not registered', $entity->getDevice()),
					[
						'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
						'type' => 'device-property-message-consumer',
						'device' => [
							'identifier' => $entity->getDevice(),
						],
					],
				);

				return true;
			}

			$propertyItem = $this->propertiesDataStorageRepository->findByIdentifier(
				$deviceItem->getId(),
				$entity->getProperty(),
			);

			if ($propertyItem instanceof MetadataEntities\DevicesModule\DeviceVariableProperty) {
				$property = $this->databaseHelper->query(
					function () use ($propertyItem): DevicesModuleEntities\Devices\Properties\Property|null {
						$findPropertyQuery = new DevicesModuleQueries\FindDeviceProperties();
						$findPropertyQuery->byId($propertyItem->getId());

						return $this->propertiesRepository->findOneBy($findPropertyQuery);
					},
				);
				assert($property instanceof DevicesModuleEntities\Devices\Properties\Property);

				if ($property instanceof DevicesModuleEntities\Devices\Properties\Variable) {
					$this->databaseHelper->transaction(function () use ($entity, $property): void {
						$this->propertiesManager->update($property, Utils\ArrayHash::from([
							'value' => $entity->getValue(),
						]));
					});
				}
			} elseif ($propertyItem instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty) {
				try {
					$propertyState = $this->propertyStateRepository->findOne($propertyItem);

				} catch (DevicesModuleExceptions\NotImplemented) {
					$this->logger->warning(
						'States repository is not configured. State could not be fetched',
						[
							'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
							'type' => 'device-property-message-consumer',
							'device' => [
								'id' => $deviceItem->getId()->toString(),
							],
							'property' => [
								'id' => $propertyItem->getId()->toString(),
							],
						],
					);

					return true;
				}

				$actualValue = DevicesModuleUtilities\ValueHelper::flattenValue(
					DevicesModuleUtilities\ValueHelper::normalizeValue(
						$propertyItem->getDataType(),
						$entity->getValue(),
						$propertyItem->getFormat(),
						$propertyItem->getInvalid(),
					),
				);

				try {
					// In case synchronization failed...
					if ($propertyState === null) {
						// ...create state in storage
						$this->propertiesStatesManager->create(
							$propertyItem,
							Utils\ArrayHash::from(array_merge(
								$propertyItem->toArray(),
								[
									'actualValue' => $actualValue,
									'expectedValue' => null,
									'pending' => false,
									'valid' => true,
								],
							)),
						);

					} else {
						$this->propertiesStatesManager->update(
							$propertyItem,
							$propertyState,
							Utils\ArrayHash::from([
								'actualValue' => $actualValue,
								'valid' => true,
							]),
						);
					}
				} catch (DevicesModuleExceptions\NotImplemented) {
					$this->logger->warning(
						'States manager is not configured. State could not be saved',
						[
							'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
							'type' => 'device-property-message-consumer',
							'device' => [
								'id' => $deviceItem->getId()->toString(),
							],
							'property' => [
								'id' => $propertyItem->getId()->toString(),
							],
						],
					);
				}
			}
		} else {
			$device = $this->databaseHelper->query(
				function () use ($entity): DevicesModuleEntities\Devices\Device|null {
					$findDeviceQuery = new DevicesModuleQueries\FindDevices();
					$findDeviceQuery->byIdentifier($entity->getDevice());

					return $this->deviceRepository->findOneBy($findDeviceQuery);
				},
			);

			if ($device === null) {
				$this->logger->error(
					sprintf('Device "%s" is not registered', $entity->getDevice()),
					[
						'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
						'type' => 'device-property-message-consumer',
						'device' => [
							'identifier' => $entity->getDevice(),
						],
					],
				);

				return true;
			}

			$property = $device->findProperty($entity->getProperty());

			if ($property === null) {
				$this->logger->error(
					sprintf('Property "%s" is not registered', $entity->getProperty()),
					[
						'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
						'type' => 'device-property-message-consumer',
						'device' => [
							'identifier' => $entity->getDevice(),
						],
						'property' => [
							'identifier' => $entity->getProperty(),
						],
					],
				);

				return true;
			}

			if (count($entity->getAttributes()) > 0) {
				$this->databaseHelper->transaction(function () use ($entity, $property): void {
					$toUpdate = $this->handlePropertyConfiguration($entity);

					if ($toUpdate !== []) {
						$this->propertiesManager->update($property, Utils\ArrayHash::from($toUpdate));
					}
				});
			}
		}

		$this->logger->debug(
			'Consumed channel property message',
			[
				'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
				'type' => 'device-property-message-consumer',
				'device' => [
					'identifier' => $entity->getDevice(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
