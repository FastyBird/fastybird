<?php declare(strict_types = 1);

/**
 * TConsumeDeviceType.php
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
 * Device type consumer trait
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read DevicesModels\Devices\DevicesRepository $devicesRepository
 * @property-read DevicesModels\Devices\Attributes\AttributesRepository $attributesRepository
 * @property-read DevicesModels\Devices\Attributes\AttributesManager $attributesManager
 * @property-read DevicesUtilities\Database $databaseHelper
 * @property-read Log\LoggerInterface $logger
 */
trait TConsumeDeviceAttribute
{

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 */
	private function setDeviceAttribute(
		Uuid\UuidInterface $deviceId,
		string|null $value,
		string $identifier,
	): void
	{
		$findAttributeQuery = new DevicesQueries\FindDeviceAttributes();
		$findAttributeQuery->byDeviceId($deviceId);
		$findAttributeQuery->byIdentifier($identifier);

		$attribute = $this->attributesRepository->findOneBy($findAttributeQuery);

		if ($attribute !== null && $value === null) {
			$this->databaseHelper->transaction(
				function () use ($attribute): void {
					$this->attributesManager->delete($attribute);
				},
			);

			return;
		}

		if ($value === null) {
			return;
		}

		if ($attribute !== null && $attribute->getContent() === $value) {
			return;
		}

		if ($attribute === null) {
			$findDeviceQuery = new DevicesQueries\FindDevices();
			$findDeviceQuery->byId($deviceId);

			$device = $this->devicesRepository->findOneBy(
				$findDeviceQuery,
				Entities\TuyaDevice::class,
			);
			assert($device instanceof Entities\TuyaDevice || $device === null);

			if ($device === null) {
				return;
			}

			$attribute = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Devices\Attributes\Attribute => $this->attributesManager->create(
					Utils\ArrayHash::from([
						'device' => $device,
						'identifier' => $identifier,
						'content' => $value,
					]),
				),
			);

			$this->logger->debug(
				'Device attribute was created',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'message-consumer',
					'device' => [
						'id' => $deviceId->toString(),
					],
					'attribute' => [
						'id' => $attribute->getPlainId(),
						'identifier' => $identifier,
					],
				],
			);

		} else {
			$attribute = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Devices\Attributes\Attribute => $this->attributesManager->update(
					$attribute,
					Utils\ArrayHash::from([
						'content' => $value,
					]),
				),
			);

			$this->logger->debug(
				'Device attribute was updated',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'message-consumer',
					'device' => [
						'id' => $deviceId->toString(),
					],
					'attribute' => [
						'id' => $attribute->getPlainId(),
						'identifier' => $identifier,
					],
				],
			);
		}
	}

}
