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
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
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
		private readonly DevicesModels\DataStorage\DevicesRepository $devicesDataStorageRepository,
		private readonly DevicesModels\DataStorage\DevicePropertiesRepository $propertiesDataStorageRepository,
		private readonly DevicesModels\DataStorage\DeviceAttributesRepository $attributesDataStorageRepository,
		private readonly DevicesModels\DataStorage\ChannelPropertiesRepository $channelsPropertiesDataStorageRepository,
		private readonly Helpers\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
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

		$deviceItem = $this->devicesDataStorageRepository->findByIdentifier(
			$entity->getConnector(),
			$entity->getId(),
		);

		if ($deviceItem === null) {
			$connectorEntity = $this->databaseHelper->query(
				function () use ($entity): Entities\TuyaConnector|null {
					$findConnectorQuery = new DevicesQueries\FindConnectors();
					$findConnectorQuery->byId($entity->getConnector());

					$connector = $this->connectorsRepository->findOneBy(
						$findConnectorQuery,
						Entities\TuyaConnector::class,
					);
					assert($connector instanceof Entities\TuyaConnector || $connector === null);

					return $connector;
				},
			);

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
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'local-discovery-message-consumer',
					'device' => [
						'id' => $deviceEntity->getPlainId(),
						'identifier' => $entity->getId(),
						'address' => $entity->getIpAddress(),
					],
				],
			);
		} else {
			$deviceEntity = $this->databaseHelper->query(
				function () use ($deviceItem): Entities\TuyaDevice|null {
					$findDeviceQuery = new DevicesQueries\FindDevices();
					$findDeviceQuery->byId($deviceItem->getId());

					$deviceEntity = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\TuyaDevice::class,
					);
					assert($deviceEntity instanceof Entities\TuyaDevice || $deviceEntity === null);

					return $deviceEntity;
				},
			);

			if ($deviceEntity === null) {
				$this->logger->error(
					'Device could not be updated',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'local-discovery-message-consumer',
						'device' => [
							'id' => $deviceItem->getId()->toString(),
						],
					],
				);

				return false;
			}
		}

		$deviceItem = $this->devicesDataStorageRepository->findByIdentifier(
			$entity->getConnector(),
			$entity->getId(),
		);

		if ($deviceItem === null) {
			$this->logger->error(
				'Newly created device could not be loaded',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
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
			$deviceItem->getId(),
			$entity->getIpAddress(),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
		);
		$this->setDeviceProperty(
			$deviceItem->getId(),
			$entity->getVersion(),
			Types\DevicePropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION,
		);
		$this->setDeviceProperty(
			$deviceItem->getId(),
			$entity->getLocalKey(),
			Types\DevicePropertyIdentifier::IDENTIFIER_LOCAL_KEY,
		);
		$this->setDeviceProperty(
			$deviceItem->getId(),
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
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
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
								'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
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
				'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
				'type' => 'local-discovery-message-consumer',
				'device' => [
					'id' => $deviceItem->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
