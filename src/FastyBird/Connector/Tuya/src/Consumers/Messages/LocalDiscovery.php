<?php declare(strict_types = 1);

/**
 * LocalDiscovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 * @since          0.13.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Tuya\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\Tuya\Consumers\Consumer;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Psr\Log;
use function assert;

/**
 * Local device discovery message consumer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LocalDiscovery implements Consumer
{

	use Nette\SmartObject;
	use TConsumeDeviceProperty;
	use TConsumeDeviceAttribute;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Devices\Attributes\AttributesRepository $attributesRepository,
		private readonly DevicesModels\Devices\Attributes\AttributesManager $attributesManager,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesModels\DataStorage\DevicePropertiesRepository $propertiesDataStorageRepository,
		private readonly DevicesModels\DataStorage\ChannelPropertiesRepository $channelsPropertiesDataStorageRepository,
		private readonly DevicesUtilities\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

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
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DiscoveredLocalDevice) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getId());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\TuyaDevice::class);

		if ($device === null) {
			$findConnectorQuery = new DevicesQueries\FindConnectors();
			$findConnectorQuery->byId($entity->getConnector());

			$connectorEntity = $this->connectorsRepository->findOneBy(
				$findConnectorQuery,
				Entities\TuyaConnector::class,
			);
			assert($connectorEntity instanceof Entities\TuyaConnector || $connectorEntity === null);

			if ($connectorEntity === null) {
				return true;
			}

			$deviceEntity = $this->databaseHelper->transaction(
				function () use ($entity, $connectorEntity): Entities\TuyaDevice {
					$deviceEntity = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\TuyaDevice::class,
						'connector' => $connectorEntity,
						'identifier' => $entity->getId(),
					]));
					assert($deviceEntity instanceof Entities\TuyaDevice);

					return $deviceEntity;
				},
			);

			$this->logger->info(
				'Creating new device',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-discovery-message-consumer',
					'device' => [
						'id' => $deviceEntity->getPlainId(),
						'identifier' => $entity->getId(),
						'address' => $entity->getIpAddress(),
					],
				],
			);
		} else {
			$findDeviceQuery = new DevicesQueries\FindDevices();
			$findDeviceQuery->byId($device->getId());

			$deviceEntity = $this->devicesRepository->findOneBy(
				$findDeviceQuery,
				Entities\TuyaDevice::class,
			);
			assert($deviceEntity instanceof Entities\TuyaDevice || $deviceEntity === null);

			if ($deviceEntity === null) {
				$this->logger->error(
					'Device could not be updated',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'local-discovery-message-consumer',
						'device' => [
							'id' => $device->getId()->toString(),
						],
					],
				);

				return false;
			}
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getId());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\TuyaDevice::class);

		if ($device === null) {
			$this->logger->error(
				'Newly created device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'local-discovery-message-consumer',
					'device' => [
						'identifier' => $entity->getId(),
						'address' => $entity->getIpAddress(),
					],
				],
			);

			return true;
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getVersion(),
			Types\DevicePropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getLocalKey(),
			Types\DevicePropertyIdentifier::IDENTIFIER_LOCAL_KEY,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->isEncrypted(),
			Types\DevicePropertyIdentifier::IDENTIFIER_ENCRYPTED,
		);

		$this->databaseHelper->transaction(function () use ($entity, $deviceEntity): bool {
			$findChannelQuery = new DevicesQueries\FindChannels();
			$findChannelQuery->byIdentifier(Types\DataPoint::DATA_POINT_LOCAL);
			$findChannelQuery->forDevice($deviceEntity);

			$channelEntity = $this->channelsRepository->findOneBy($findChannelQuery);

			if ($channelEntity === null) {
				$channelEntity = $this->channelsManager->create(Utils\ArrayHash::from([
					'device' => $deviceEntity,
					'identifier' => Types\DataPoint::DATA_POINT_LOCAL,
				]));

				$this->logger->debug(
					'Creating new device channel',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'local-discovery-message-consumer',
						'device' => [
							'id' => $deviceEntity->getPlainId(),
						],
						'channel' => [
							'id' => $channelEntity->getPlainId(),
						],
					],
				);
			}

			foreach ($entity->getDataPoints() as $dataPoint) {
				$propertyItem = $this->channelsPropertiesDataStorageRepository->findByIdentifier(
					$channelEntity->getId(),
					$dataPoint->getCode(),
				);

				if ($propertyItem === null) {
					$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'channel' => $channelEntity,
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => $dataPoint->getCode(),
						'name' => $dataPoint->getCode(),
						'dataType' => $dataPoint->getDataType(),
						'unit' => $dataPoint->getUnit(),
						'format' => $dataPoint->getFormat(),
						'queryable' => $dataPoint->isQueryable(),
						'settable' => $dataPoint->isSettable(),
					]));

				} else {
					$findPropertyQuery = new DevicesQueries\FindChannelProperties();
					$findPropertyQuery->byId($propertyItem->getId());

					$propertyEntity = $this->channelsPropertiesRepository->findOneBy($findPropertyQuery);

					if ($propertyEntity !== null) {
						$this->channelsPropertiesManager->update($propertyEntity, Utils\ArrayHash::from([
							'name' => $propertyEntity->getName() ?? $dataPoint->getCode(),
							'dataType' => $dataPoint->getDataType(),
							'unit' => $dataPoint->getUnit(),
							'format' => $dataPoint->getFormat(),
							'queryable' => $dataPoint->isQueryable(),
							'settable' => $dataPoint->isSettable(),
						]));

					} else {
						$this->logger->error(
							'Channel property could not be updated',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'local-discovery-message-consumer',
								'device' => [
									'id' => $channelEntity->getDevice()->getId()->toString(),
								],
								'channel' => [
									'id' => $channelEntity->getId()->toString(),
								],
								'property' => [
									'id' => $propertyItem->getId(),
								],
							],
						);
					}
				}
			}

			return true;
		});

		$this->logger->debug(
			'Consumed device found message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'local-discovery-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
