<?php declare(strict_types = 1);

/**
 * StoreLocalDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function assert;
use function strval;

/**
 * Store locally found device details message consumer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreLocalDevice implements Queue\Consumer
{

	use Nette\SmartObject;
	use DeviceProperty;

	public function __construct(
		protected readonly Shelly\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesUtilities\Database $databaseHelper,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreLocalDevice) {
			return false;
		}

		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		if ($device === null) {
			$findConnectorQuery = new Queries\FindConnectors();
			$findConnectorQuery->byId($entity->getConnector());

			$connector = $this->connectorsRepository->findOneBy(
				$findConnectorQuery,
				Entities\ShellyConnector::class,
			);

			if ($connector === null) {
				return true;
			}

			$device = $this->databaseHelper->transaction(
				function () use ($entity, $connector): Entities\ShellyDevice {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\ShellyDevice::class,
						'connector' => $connector,
						'identifier' => $entity->getIdentifier(),
					]));
					assert($device instanceof Entities\ShellyDevice);

					return $device;
				},
			);

			$this->logger->debug(
				'Device was created',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'store-local-device-message-consumer',
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $entity->getIdentifier(),
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
			$entity->getDomain(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::DOMAIN,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::DOMAIN),
		);
		$this->setDeviceProperty(
			$device->getId(),
			strval($entity->getGeneration()->getValue()),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
			Types\DevicePropertyIdentifier::GENERATION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::GENERATION),
			[Types\DeviceGeneration::GENERATION_1, Types\DeviceGeneration::GENERATION_2],
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->isAuthEnabled(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
			Types\DevicePropertyIdentifier::AUTH_ENABLED,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::AUTH_ENABLED),
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
			$entity->getMacAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::MAC_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getFirmwareVersion(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::FIRMWARE_VERSION),
		);

		foreach ($entity->getChannels() as $channelDescription) {
			$findChannelQuery = new DevicesQueries\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($channelDescription->getIdentifier());

			$channel = $this->channelsRepository->findOneBy($findChannelQuery);

			$channel = $channel === null ? $this->databaseHelper->transaction(
				fn (): DevicesEntities\Channels\Channel => $this->channelsManager->create(Utils\ArrayHash::from([
					'device' => $device,
					'identifier' => $channelDescription->getIdentifier(),
					'name' => $channelDescription->getName(),
				])),
			) : $this->databaseHelper->transaction(
				fn (): DevicesEntities\Channels\Channel => $this->channelsManager->update(
					$channel,
					Utils\ArrayHash::from([
						'device' => $device,
						'identifier' => $channelDescription->getIdentifier(),
						'name' => $channelDescription->getName(),
					]),
				),
			);

			foreach ($channelDescription->getProperties() as $propertyDescription) {
				$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
				$findChannelPropertyQuery->forChannel($channel);
				$findChannelPropertyQuery->byIdentifier($propertyDescription->getIdentifier());

				$channelProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

				if ($channelProperty === null) {
					$channelProperty = $this->databaseHelper->transaction(
						fn (): DevicesEntities\Channels\Properties\Property => $this->channelsPropertiesManager->create(
							Utils\ArrayHash::from([
								'channel' => $channel,
								'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
								'identifier' => $propertyDescription->getIdentifier(),
								'name' => $propertyDescription->getName(),
								'unit' => $propertyDescription->getUnit(),
								'dataType' => $propertyDescription->getDataType(),
								'format' => $propertyDescription->getFormat(),
								'invalid' => $propertyDescription->getInvalid(),
								'queryable' => $propertyDescription->isQueryable(),
								'settable' => $propertyDescription->isSettable(),
							]),
						),
					);

					$this->logger->debug(
						'Device channel property was created',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'store-local-device-message-consumer',
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'channel' => [
								'id' => $channelProperty->getChannel()->getId()->toString(),
							],
							'property' => [
								'id' => $channelProperty->getId()->toString(),
							],
						],
					);

				} else {
					$channelProperty = $this->databaseHelper->transaction(
						fn (): DevicesEntities\Channels\Properties\Property => $this->channelsPropertiesManager->update(
							$channelProperty,
							Utils\ArrayHash::from([
								'unit' => $propertyDescription->getUnit(),
								'dataType' => $propertyDescription->getDataType(),
								'format' => $propertyDescription->getFormat(),
								'invalid' => $propertyDescription->getInvalid(),
								'queryable' => $propertyDescription->isQueryable(),
								'settable' => $propertyDescription->isSettable(),
							]),
						),
					);

					$this->logger->debug(
						'Device channel property was updated',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'store-local-device-message-consumer',
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'channel' => [
								'id' => $channelProperty->getChannel()->getId()->toString(),
							],
							'property' => [
								'id' => $channelProperty->getId()->toString(),
							],
						],
					);
				}
			}
		}

		$this->logger->debug(
			'Consumed device found message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
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
