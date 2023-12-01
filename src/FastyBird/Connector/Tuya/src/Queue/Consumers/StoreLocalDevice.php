<?php declare(strict_types = 1);

/**
 * StoreLocalDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Tuya\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function array_merge;
use function assert;
use function count;

/**
 * Store locally found device details message consumer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreLocalDevice implements Queue\Consumer
{

	use Nette\SmartObject;
	use DeviceProperty;
	use ChannelProperty;

	public function __construct(
		protected readonly Tuya\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		protected readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStateManager,
		protected readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		protected readonly DevicesUtilities\Database $databaseHelper,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreLocalDevice) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getId());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\TuyaDevice::class);

		if ($device === null) {
			$findConnectorQuery = new Queries\Entities\FindConnectors();
			$findConnectorQuery->byId($entity->getConnector());

			$connector = $this->connectorsRepository->findOneBy(
				$findConnectorQuery,
				Entities\TuyaConnector::class,
			);

			if ($connector === null) {
				return true;
			}

			$device = $this->databaseHelper->transaction(
				function () use ($entity, $connector): Entities\TuyaDevice {
					$parents = [];

					if ($entity->getGateway() !== null) {
						$findParentDeviceQuery = new Queries\Entities\FindDevices();
						$findParentDeviceQuery->byConnectorId($entity->getConnector());
						$findParentDeviceQuery->byIdentifier($entity->getGateway());

						$parent = $this->devicesRepository->findOneBy(
							$findParentDeviceQuery,
							Entities\TuyaDevice::class,
						);

						if ($parent === null) {
							throw new Tuya\Exceptions\InvalidState(
								'Parent device could not be loaded for child device',
							);
						}

						$parents = [$parent];
					}

					$device = $this->devicesManager->create(
						Utils\ArrayHash::from(array_merge(
							[
								'entity' => Entities\TuyaDevice::class,
								'connector' => $connector,
								'identifier' => $entity->getId(),
								'name' => $entity->getName(),
							],
							$entity->getGateway() !== null
								? ['parents' => $parents]
								: [],
						)),
					);
					assert($device instanceof Entities\TuyaDevice);

					return $device;
				},
			);

			$this->logger->debug(
				'Device was created',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'store-local-device-message-consumer',
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $entity->getId(),
						'address' => $entity->getIpAddress(),
					],
					'data' => $entity->toArray(),
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IP_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getVersion(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
			Types\DevicePropertyIdentifier::PROTOCOL_VERSION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PROTOCOL_VERSION),
			[
				Types\DeviceProtocolVersion::V31,
				Types\DeviceProtocolVersion::V32,
				Types\DeviceProtocolVersion::V33,
				Types\DeviceProtocolVersion::V34,
				Types\DeviceProtocolVersion::V32_PLUS,
			],
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getLocalKey(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::LOCAL_KEY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::LOCAL_KEY),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getNodeId(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::NODE_ID,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::NODE_ID),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getGateway(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::GATEWAY_ID,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::GATEWAY_ID),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getCategory(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::CATEGORY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::CATEGORY),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIcon(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::ICON,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::ICON),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getLatitude(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::LATITUDE,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::LATITUDE),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getLongitude(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::LONGITUDE,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::LONGITUDE),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getProductId(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::PRODUCT_ID,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PRODUCT_ID),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getProductName(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::PRODUCT_NAME,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PRODUCT_NAME),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->isEncrypted(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
			Types\DevicePropertyIdentifier::ENCRYPTED,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::ENCRYPTED),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getModel(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MODEL),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getMac(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::MAC_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getSn(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::SERIAL_NUMBER,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::SERIAL_NUMBER),
		);

		if (count($entity->getDataPoints()) > 0) {
			$this->databaseHelper->transaction(function () use ($entity, $device): bool {
				$findChannelQuery = new DevicesQueries\Entities\FindChannels();
				$findChannelQuery->byIdentifier(Types\DataPoint::LOCAL);
				$findChannelQuery->forDevice($device);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery);

				if ($channel === null) {
					$channel = $this->channelsManager->create(Utils\ArrayHash::from([
						'device' => $device,
						'identifier' => Types\DataPoint::LOCAL,
					]));

					$this->logger->debug(
						'Device channel was created',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'store-local-device-message-consumer',
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'channel' => [
								'id' => $channel->getId()->toString(),
							],
						],
					);
				}

				foreach ($entity->getDataPoints() as $dataPoint) {
					$this->setChannelProperty(
						DevicesEntities\Channels\Properties\Dynamic::class,
						$channel->getId(),
						null,
						$dataPoint->getDataType(),
						$dataPoint->getCode(),
						$dataPoint->getCode(),
						$dataPoint->getFormat(),
						$dataPoint->getUnit(),
						null,
						null,
						$dataPoint->getStep(),
						$dataPoint->isSettable(),
						$dataPoint->isQueryable(),
					);
				}

				return true;
			});
		}

		$this->logger->debug(
			'Consumed store device message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'store-local-device-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
