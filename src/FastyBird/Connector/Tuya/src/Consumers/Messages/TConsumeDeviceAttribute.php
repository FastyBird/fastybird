<?php declare(strict_types = 1);

/**
 * TConsumeDeviceType.php
 *
 * @license        More in license.md
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
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use FastyBird\Metadata;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use Nette\Utils;
use Psr\Log;
use Ramsey\Uuid;
use function assert;

/**
 * Device type consumer trait
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read DevicesModuleModels\Devices\DevicesRepository $devicesRepository
 * @property-read DevicesModuleModels\Devices\Attributes\AttributesRepository $attributesRepository
 * @property-read DevicesModuleModels\Devices\Attributes\AttributesManager $attributesManager
 * @property-read DevicesModuleModels\DataStorage\DeviceAttributesRepository $attributesDataStorageRepository
 * @property-read Helpers\Database $databaseHelper
 * @property-read Log\LoggerInterface $logger
 */
trait TConsumeDeviceAttribute
{

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function setDeviceAttribute(
		Uuid\UuidInterface $deviceId,
		string|null $value,
		string $identifier,
	): void
	{
		$attributeItem = $this->attributesDataStorageRepository->findByIdentifier(
			$deviceId,
			$identifier,
		);

		if ($attributeItem !== null && $value === null) {
			$attributeEntity = $this->databaseHelper->query(
				function () use ($attributeItem): DevicesModuleEntities\Devices\Attributes\Attribute|null {
					$findAttributeQuery = new DevicesModuleQueries\FindDeviceAttributes();
					$findAttributeQuery->byId($attributeItem->getId());

					return $this->attributesRepository->findOneBy($findAttributeQuery);
				},
			);

			if ($attributeEntity !== null) {
				$this->databaseHelper->transaction(
					function () use ($attributeEntity): void {
						$this->attributesManager->delete($attributeEntity);
					},
				);
			}

			return;
		}

		if ($value === null) {
			return;
		}

		if ($attributeItem !== null && $attributeItem->getContent() === $value) {
			return;
		}

		if ($attributeItem === null) {
			$deviceEntity = $this->databaseHelper->query(
				function () use ($deviceId): Entities\TuyaDevice|null {
					$findDeviceQuery = new DevicesModuleQueries\FindDevices();
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

			$attributeEntity = $this->databaseHelper->transaction(
				fn (): DevicesModuleEntities\Devices\Attributes\Attribute => $this->attributesManager->create(
					Utils\ArrayHash::from([
						'device' => $deviceEntity,
						'identifier' => $identifier,
						'content' => $value,
					]),
				),
			);

			$this->logger->debug(
				'Device attribute was created',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'message-consumer',
					'device' => [
						'id' => $deviceId->toString(),
					],
					'attribute' => [
						'id' => $attributeEntity->getPlainId(),
						'identifier' => $identifier,
					],
				],
			);

		} else {
			$attributeEntity = $this->databaseHelper->query(
				function () use ($attributeItem): DevicesModuleEntities\Devices\Attributes\Attribute|null {
					$findAttributeQuery = new DevicesModuleQueries\FindDeviceAttributes();
					$findAttributeQuery->byId($attributeItem->getId());

					return $this->attributesRepository->findOneBy($findAttributeQuery);
				},
			);

			if ($attributeEntity !== null) {
				$attributeEntity = $this->databaseHelper->transaction(
					fn (): DevicesModuleEntities\Devices\Attributes\Attribute => $this->attributesManager->update(
						$attributeEntity,
						Utils\ArrayHash::from([
							'content' => $value,
						]),
					),
				);

				$this->logger->debug(
					'Device attribute was updated',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'message-consumer',
						'device' => [
							'id' => $deviceId->toString(),
						],
						'attribute' => [
							'id' => $attributeEntity->getPlainId(),
							'identifier' => $identifier,
						],
					],
				);

			} else {
				$this->logger->error(
					'Device attribute could not be updated',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'message-consumer',
						'device' => [
							'id' => $deviceId->toString(),
						],
						'attribute' => [
							'id' => $attributeItem->getId()->toString(),
							'identifier' => $identifier,
						],
					],
				);
			}
		}
	}

}
