<?php declare(strict_types = 1);

/**
 * TConsumeDeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 * @since          0.13.0
 *
 * @date           31.08.22
 */

namespace FastyBird\Connector\Tuya\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette\Utils;
use Psr\Log;
use Ramsey\Uuid;
use function assert;

/**
 * Device ip address consumer trait
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read DevicesModels\Devices\DevicesRepository $devicesRepository
 * @property-read DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository
 * @property-read DevicesModels\Devices\Properties\PropertiesManager $propertiesManager
 * @property-read DevicesModels\DataStorage\DevicePropertiesRepository $propertiesDataStorageRepository
 * @property-read DevicesUtilities\Database $databaseHelper
 * @property-read Log\LoggerInterface $logger
 */
trait TConsumeDeviceProperty
{

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function setDeviceProperty(
		Uuid\UuidInterface $deviceId,
		string|bool|null $value,
		string $identifier,
	): void
	{
		$propertyItem = $this->propertiesDataStorageRepository->findByIdentifier(
			$deviceId,
			$identifier,
		);

		if ($propertyItem !== null && $value === null) {
			$propertyEntity = $this->databaseHelper->query(
				function () use ($propertyItem): DevicesEntities\Devices\Properties\Property|null {
					$findPropertyQuery = new DevicesQueries\FindDeviceProperties();
					$findPropertyQuery->byId($propertyItem->getId());

					return $this->propertiesRepository->findOneBy($findPropertyQuery);
				},
			);

			if ($propertyEntity !== null) {
				$this->databaseHelper->transaction(
					function () use ($propertyEntity): void {
						$this->propertiesManager->delete($propertyEntity);
					},
				);
			}

			return;
		}

		if ($value === null) {
			return;
		}

		if (
			$propertyItem instanceof MetadataEntities\DevicesModule\DeviceVariableProperty
			&& $propertyItem->getValue() === $value
		) {
			return;
		}

		if (
			$propertyItem !== null
			&& !$propertyItem instanceof MetadataEntities\DevicesModule\DeviceVariableProperty
		) {
			$propertyEntity = $this->databaseHelper->query(
				function () use ($propertyItem): DevicesEntities\Devices\Properties\Property|null {
					$findPropertyQuery = new DevicesQueries\FindDeviceProperties();
					$findPropertyQuery->byId($propertyItem->getId());

					return $this->propertiesRepository->findOneBy($findPropertyQuery);
				},
			);

			if ($propertyEntity !== null) {
				$this->databaseHelper->transaction(function () use ($propertyEntity): void {
					$this->propertiesManager->delete($propertyEntity);
				});

				$this->logger->warning(
					'Device property is not valid type',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'message-consumer',
						'device' => [
							'id' => $deviceId->toString(),
						],
						'property' => [
							'id' => $propertyEntity->getPlainId(),
							'identifier' => $identifier,
						],
					],
				);
			}

			$propertyItem = null;
		}

		if ($propertyItem === null) {
			$deviceEntity = $this->databaseHelper->query(
				function () use ($deviceId): Entities\TuyaDevice|null {
					$findDeviceQuery = new DevicesQueries\FindDevices();
					$findDeviceQuery->byId($deviceId);

					$deviceEntity = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\TuyaDevice::class,
					);
					assert($deviceEntity instanceof Entities\TuyaDevice || $deviceEntity === null);

					return $deviceEntity;
				},
			);

			if ($deviceEntity === null) {
				return;
			}

			$propertyEntity = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Devices\Properties\Property => $this->propertiesManager->create(
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'device' => $deviceEntity,
						'identifier' => $identifier,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
						'settable' => false,
						'queryable' => false,
						'value' => $value,
					]),
				),
			);

			$this->logger->debug(
				'Device ip address property was created',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'message-consumer',
					'device' => [
						'id' => $deviceId->toString(),
					],
					'property' => [
						'id' => $propertyEntity->getPlainId(),
						'identifier' => $identifier,
					],
				],
			);

		} else {
			$propertyEntity = $this->databaseHelper->query(
				function () use ($propertyItem): DevicesEntities\Devices\Properties\Property|null {
					$findPropertyQuery = new DevicesQueries\FindDeviceProperties();
					$findPropertyQuery->byId($propertyItem->getId());

					return $this->propertiesRepository->findOneBy($findPropertyQuery);
				},
			);

			if ($propertyEntity !== null) {
				$propertyEntity = $this->databaseHelper->transaction(
					fn (): DevicesEntities\Devices\Properties\Property => $this->propertiesManager->update(
						$propertyEntity,
						Utils\ArrayHash::from([
							'value' => $value,
						]),
					),
				);

				$this->logger->debug(
					'Device ip address property was updated',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'message-consumer',
						'device' => [
							'id' => $deviceId->toString(),
						],
						'property' => [
							'id' => $propertyEntity->getPlainId(),
							'identifier' => $identifier,
						],
					],
				);

			} else {
				$this->logger->error(
					'Device ip address property could not be updated',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'message-consumer',
						'device' => [
							'id' => $deviceId->toString(),
						],
						'property' => [
							'id' => $propertyItem->getId()->toString(),
							'identifier' => $identifier,
						],
					],
				);
			}
		}
	}

}
