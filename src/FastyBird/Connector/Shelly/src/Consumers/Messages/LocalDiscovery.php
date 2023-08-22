<?php declare(strict_types = 1);

/**
 * LocalDiscovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\Shelly\Consumers\Consumer;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queries;
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
use Psr\Log;
use function assert;
use function strval;

/**
 * Device local discovery message consumer
 *
 * @package        FastyBird:ShellyConnector!
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
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
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

		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->startWithIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		if ($device === null) {
			$findConnectorQuery = new Queries\FindConnectors();
			$findConnectorQuery->byId($entity->getConnector());

			$connector = $this->connectorsRepository->findOneBy($findConnectorQuery, Entities\ShellyConnector::class);

			if ($connector === null) {
				$this->logger->error(
					'Error during loading connector',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'local-discovery-message-consumer',
						'connector' => [
							'id' => $entity->getConnector()->toString(),
						],
					],
				);

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

			$this->logger->info(
				'New device was created',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'local-discovery-message-consumer',
					'device' => [
						'id' => $device->getPlainId(),
					],
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IP_ADDRESS,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getDomain(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::DOMAIN,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::DOMAIN),
		);
		$this->setDeviceProperty(
			$device->getId(),
			strval($entity->getGeneration()->getValue()),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
			Types\DevicePropertyIdentifier::GENERATION,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::GENERATION),
			[Types\DeviceGeneration::GENERATION_1, Types\DeviceGeneration::GENERATION_2],
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->isAuthEnabled(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
			Types\DevicePropertyIdentifier::AUTH_ENABLED,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::AUTH_ENABLED),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getModel(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::MODEL,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::MODEL),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getMacAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::MAC_ADDRESS,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getFirmwareVersion(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::FIRMWARE_VERSION),
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

				$channelProperty = $this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery);

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
							'type' => 'local-discovery-message-consumer',
							'device' => [
								'id' => $device->getPlainId(),
							],
							'channel' => [
								'id' => $channelProperty->getChannel()->getPlainId(),
							],
							'property' => [
								'id' => $channelProperty->getPlainId(),
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
							'type' => 'local-discovery-message-consumer',
							'device' => [
								'id' => $device->getPlainId(),
							],
							'channel' => [
								'id' => $channelProperty->getChannel()->getPlainId(),
							],
							'property' => [
								'id' => $channelProperty->getPlainId(),
							],
						],
					);
				}
			}
		}

		$this->logger->debug(
			'Consumed device discovery message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
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
