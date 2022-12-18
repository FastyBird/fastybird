<?php declare(strict_types = 1);

/**
 * CloudDiscovery.php
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
 * Cloud device discovery message consumer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CloudDiscovery implements Consumer
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
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DiscoveredCloudDevice) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getId());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\TuyaDevice::class);

		if ($device === null) {
			$findConnectorQuery = new DevicesQueries\FindConnectors();
			$findConnectorQuery->byId($entity->getConnector());

			$connector = $this->connectorsRepository->findOneBy(
				$findConnectorQuery,
				Entities\TuyaConnector::class,
			);
			assert($connector instanceof Entities\TuyaConnector || $connector === null);

			if ($connector === null) {
				return true;
			}

			$device = $this->databaseHelper->transaction(
				function () use ($entity, $connector): Entities\TuyaDevice {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\TuyaDevice::class,
						'connector' => $connector,
						'identifier' => $entity->getId(),
						'name' => $entity->getName(),
					]));
					assert($device instanceof Entities\TuyaDevice);

					return $device;
				},
			);

			$this->logger->info(
				'Creating new device',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'cloud-discovery-message-consumer',
					'device' => [
						'id' => $device->getPlainId(),
						'identifier' => $entity->getId(),
						'address' => $entity->getIpAddress(),
						'name' => $entity->getName(),
					],
				],
			);
		} else {
			$device = $this->databaseHelper->transaction(
				function () use ($entity, $device): Entities\TuyaDevice {
					$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
						'name' => $entity->getName(),
					]));
					assert($device instanceof Entities\TuyaDevice);

					return $device;
				},
			);

			$this->logger->debug(
				'Device was updated',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'cloud-discovery-message-consumer',
					'device' => [
						'id' => $device->getPlainId(),
					],
				],
			);
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
					'type' => 'cloud-discovery-message-consumer',
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
			$entity->getLocalKey(),
			Types\DevicePropertyIdentifier::IDENTIFIER_LOCAL_KEY,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getCategory(),
			Types\DevicePropertyIdentifier::IDENTIFIER_CATEGORY,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIcon(),
			Types\DevicePropertyIdentifier::IDENTIFIER_ICON,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getLatitude(),
			Types\DevicePropertyIdentifier::IDENTIFIER_LATITUDE,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getLongitude(),
			Types\DevicePropertyIdentifier::IDENTIFIER_LONGITUDE,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getProductId(),
			Types\DevicePropertyIdentifier::IDENTIFIER_PRODUCT_ID,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getProductName(),
			Types\DevicePropertyIdentifier::IDENTIFIER_PRODUCT_NAME,
		);

		$this->setDeviceAttribute(
			$device->getId(),
			$entity->getModel(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MODEL,
		);
		$this->setDeviceAttribute(
			$device->getId(),
			$entity->getMac(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS,
		);
		$this->setDeviceAttribute(
			$device->getId(),
			$entity->getSn(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_SERIAL_NUMBER,
		);

		$this->databaseHelper->transaction(function () use ($entity, $device): bool {
			$findChannelQuery = new DevicesQueries\FindChannels();
			$findChannelQuery->byIdentifier(Types\DataPoint::DATA_POINT_CLOUD);
			$findChannelQuery->forDevice($device);

			$channel = $this->channelsRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				$channel = $this->channelsManager->create(Utils\ArrayHash::from([
					'device' => $device,
					'identifier' => Types\DataPoint::DATA_POINT_CLOUD,
				]));

				$this->logger->debug(
					'Creating new device channel',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'cloud-discovery-message-consumer',
						'device' => [
							'id' => $device->getPlainId(),
						],
						'channel' => [
							'id' => $channel->getPlainId(),
						],
					],
				);
			}

			foreach ($entity->getDataPoints() as $dataPoint) {
				$property = $channel->findProperty($dataPoint->getCode());

				if ($property === null) {
					$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'channel' => $channel,
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
					$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
						'name' => $property->getName() ?? $dataPoint->getCode(),
						'dataType' => $dataPoint->getDataType(),
						'unit' => $dataPoint->getUnit(),
						'format' => $dataPoint->getFormat(),
						'queryable' => $dataPoint->isQueryable(),
						'settable' => $dataPoint->isSettable(),
					]));
				}
			}

			return true;
		});

		$this->logger->debug(
			'Consumed device found message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'cloud-discovery-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
