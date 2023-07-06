<?php declare(strict_types = 1);

/**
 * ConsumeDeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           28.06.23
 */

namespace FastyBird\Connector\Viera\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\Viera\Entities;
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
use function array_merge;
use function assert;

/**
 * Device property consumer trait
 *
 * @package        FastyBird:VieraConnector!
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
	 * @param class-string<DevicesEntities\Devices\Properties\Variable|DevicesEntities\Devices\Properties\Dynamic> $type
	 * @param string|array<int, string>|array<int, string|int|float|array<int, string|int|float>|Utils\ArrayHash|null>|array<int, array<int, string|array<int, string|int|float|bool>|Utils\ArrayHash|null>>|null $format
	 *
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function setDeviceProperty(
		string $type,
		Uuid\UuidInterface $deviceId,
		string|bool|int|null $value,
		MetadataTypes\DataType $dataType,
		string $identifier,
		string|null $name = null,
		array|string|null $format = null,
		bool $settable = false,
		bool $queryable = false,
	): void
	{
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->byDeviceId($deviceId);
		$findDevicePropertyQuery->byIdentifier($identifier);

		$property = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

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
			$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
			$findDevicePropertyQuery->byId($property->getId());

			$property = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

			if ($property !== null) {
				$this->databaseHelper->transaction(function () use ($property): void {
					$this->propertiesManager->delete($property);
				});

				$this->logger->warning(
					'Device property is not valid type',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
						'type' => 'message-consumer',
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
				Entities\VieraDevice::class,
			);
			assert($device instanceof Entities\VieraDevice || $device === null);

			if ($device === null) {
				return;
			}

			$property = $this->databaseHelper->transaction(
				fn (): DevicesEntities\Devices\Properties\Property => $this->propertiesManager->create(
					Utils\ArrayHash::from(array_merge(
						[
							'entity' => DevicesEntities\Devices\Properties\Variable::class,
							'device' => $device,
							'identifier' => $identifier,
							'name' => $name,
							'dataType' => $dataType,
							'settable' => $settable,
							'queryable' => $queryable,
							'format' => $format,
						],
						$type === DevicesEntities\Devices\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [],
					)),
				),
			);

			$this->logger->debug(
				'Device property was created',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'message-consumer',
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
					Utils\ArrayHash::from(array_merge(
						[
							'dataType' => $dataType,
							'settable' => $settable,
							'queryable' => $queryable,
							'format' => $format,
						],
						$type === DevicesEntities\Devices\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [],
					)),
				),
			);

			$this->logger->debug(
				'Device property was updated',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'message-consumer',
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
