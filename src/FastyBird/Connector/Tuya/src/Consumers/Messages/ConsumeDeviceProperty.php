<?php declare(strict_types = 1);

/**
 * ConsumeDeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           31.08.22
 */

namespace FastyBird\Connector\Tuya\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\Tuya\Entities;
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
 * @property-read DevicesUtilities\Database $databaseHelper
 * @property-read Log\LoggerInterface $logger
 */
trait ConsumeDeviceProperty
{

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function setDeviceProperty(
		Uuid\UuidInterface $deviceId,
		string|bool|null $value,
		string $identifier,
	): void
	{
		$findPropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findPropertyQuery->byDeviceId($deviceId);
		$findPropertyQuery->byIdentifier($identifier);

		$property = $this->propertiesRepository->findOneBy($findPropertyQuery);

		if ($property !== null && $value === null) {
			$this->databaseHelper->transaction(
				function () use ($property): void {
					$this->propertiesManager->delete($property);
				},
			);

			return;
		}

		if ($value === null) {
			return;
		}

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& $property->getValue() === $value
		) {
			return;
		}

		if (
			$property !== null
			&& !$property instanceof DevicesEntities\Devices\Properties\Variable
		) {
			$findPropertyQuery = new DevicesQueries\FindDeviceProperties();
			$findPropertyQuery->byId($property->getId());

			$property = $this->propertiesRepository->findOneBy($findPropertyQuery);

			if ($property !== null) {
				$this->databaseHelper->transaction(function () use ($property): void {
					$this->propertiesManager->delete($property);
				});

				$this->logger->warning(
					'Device property is not valid type',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'message-consumer',
						'group' => 'consumer',
						'device' => [
							'id' => $deviceId->toString(),
						],
						'property' => [
							'id' => $property->getPlainId(),
							'identifier' => $identifier,
						],
					],
				);
			}

			$property = null;
		}

		if ($property === null) {
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

			$property = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Devices\Properties\Property => $this->propertiesManager->create(
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'device' => $device,
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
					'group' => 'consumer',
					'device' => [
						'id' => $deviceId->toString(),
					],
					'property' => [
						'id' => $property->getPlainId(),
						'identifier' => $identifier,
					],
				],
			);

		} else {
			$property = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Devices\Properties\Property => $this->propertiesManager->update(
					$property,
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
					'group' => 'consumer',
					'device' => [
						'id' => $deviceId->toString(),
					],
					'property' => [
						'id' => $property->getPlainId(),
						'identifier' => $identifier,
					],
				],
			);
		}
	}

}
