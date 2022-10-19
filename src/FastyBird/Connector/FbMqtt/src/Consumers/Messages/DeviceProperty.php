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

namespace FastyBird\Connector\FbMqtt\Consumers\Messages;

use Doctrine\DBAL;
use Exception;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Psr\Log;
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
		private readonly DevicesModels\Devices\DevicesRepository $deviceRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\DataStorage\DevicesRepository $devicesDataStorageRepository,
		private readonly DevicesModels\DataStorage\DevicePropertiesRepository $propertiesDataStorageRepository,
		private readonly DevicesModels\States\DevicePropertiesRepository $propertyStateRepository,
		private readonly DevicesModels\States\DevicePropertiesManager $propertiesStatesManager,
		private readonly Helpers\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Exception
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceProperty) {
			return false;
		}

		if ($entity->getValue() !== FbMqtt\Constants::VALUE_NOT_SET) {
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
					function () use ($propertyItem): DevicesEntities\Devices\Properties\Property|null {
						$findPropertyQuery = new DevicesQueries\FindDeviceProperties();
						$findPropertyQuery->byId($propertyItem->getId());

						return $this->propertiesRepository->findOneBy($findPropertyQuery);
					},
				);
				assert($property instanceof DevicesEntities\Devices\Properties\Property);

				if ($property instanceof DevicesEntities\Devices\Properties\Variable) {
					$this->databaseHelper->transaction(function () use ($entity, $property): void {
						$this->propertiesManager->update($property, Utils\ArrayHash::from([
							'value' => $entity->getValue(),
						]));
					});
				}
			} elseif ($propertyItem instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty) {
				try {
					$propertyState = $this->propertyStateRepository->findOne($propertyItem);

				} catch (DevicesExceptions\NotImplemented) {
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

				$actualValue = DevicesUtilities\ValueHelper::flattenValue(
					DevicesUtilities\ValueHelper::normalizeValue(
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
				} catch (DevicesExceptions\NotImplemented) {
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
				function () use ($entity): DevicesEntities\Devices\Device|null {
					$findDeviceQuery = new DevicesQueries\FindDevices();
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
