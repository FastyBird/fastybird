<?php declare(strict_types = 1);

/**
 * LocalDiscovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Tuya\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\Tuya\Consumers\Consumer;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Helpers;
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
use function count;

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
	use ConsumeDeviceProperty;

	public function __construct(
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\Database $databaseHelper,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
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
					'type' => 'local-discovery-message-consumer',
					'device' => [
						'id' => $device->getPlainId(),
						'identifier' => $entity->getId(),
						'address' => $entity->getIpAddress(),
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
					'type' => 'local-discovery-message-consumer',
					'device' => [
						'id' => $device->getPlainId(),
					],
				],
			);
		}

		if ($entity->getGateway() !== null) {
			$findParentDeviceQuery = new DevicesQueries\FindDevices();
			$findParentDeviceQuery->byConnectorId($entity->getConnector());
			$findParentDeviceQuery->byIdentifier($entity->getGateway());

			$parent = $this->devicesRepository->findOneBy($findParentDeviceQuery, Entities\TuyaDevice::class);

			if ($parent === null) {
				$this->databaseHelper->transaction(
					function () use ($device): void {
						$this->devicesManager->delete($device);
					},
				);

				return true;
			}

			$this->databaseHelper->transaction(
				function () use ($device, $parent): void {
					$this->devicesManager->update($device, Utils\ArrayHash::from([
						'parents' => [$parent],
					]));
				},
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getVersion(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
			Types\DevicePropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION),
			[
				Types\DeviceProtocolVersion::VERSION_V31,
				Types\DeviceProtocolVersion::VERSION_V32,
				Types\DeviceProtocolVersion::VERSION_V33,
				Types\DeviceProtocolVersion::VERSION_V34,
				Types\DeviceProtocolVersion::VERSION_V32_PLUS,
			],
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getLocalKey(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_LOCAL_KEY,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_LOCAL_KEY),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getNodeId(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_NODE_ID,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_NODE_ID),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getGateway(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_GATEWAY_ID,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_GATEWAY_ID),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getCategory(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_CATEGORY,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_CATEGORY),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIcon(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_ICON,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_ICON),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getLatitude(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_LATITUDE,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_LATITUDE),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getLongitude(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_LONGITUDE,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_LONGITUDE),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getProductId(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_PRODUCT_ID,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_PRODUCT_ID),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getProductName(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_PRODUCT_NAME,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_PRODUCT_NAME),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->isEncrypted(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
			Types\DevicePropertyIdentifier::IDENTIFIER_ENCRYPTED,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_ENCRYPTED),
		);

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getModel(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getMac(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getSn(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_SERIAL_NUMBER,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_SERIAL_NUMBER),
		);

		if (count($entity->getDataPoints()) > 0) {
			$this->databaseHelper->transaction(function () use ($entity, $device): bool {
				$findChannelQuery = new DevicesQueries\FindChannels();
				$findChannelQuery->byIdentifier(Types\DataPoint::DATA_POINT_LOCAL);
				$findChannelQuery->forDevice($device);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery);

				if ($channel === null) {
					$channel = $this->channelsManager->create(Utils\ArrayHash::from([
						'device' => $device,
						'identifier' => Types\DataPoint::DATA_POINT_LOCAL,
					]));

					$this->logger->debug(
						'Creating new device channel',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'local-discovery-message-consumer',
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
					$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
					$findChannelPropertyQuery->forChannel($channel);
					$findChannelPropertyQuery->byIdentifier($dataPoint->getCode());

					$property = $this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery);

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
		}

		$this->logger->debug(
			'Consumed device found message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'local-discovery-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
