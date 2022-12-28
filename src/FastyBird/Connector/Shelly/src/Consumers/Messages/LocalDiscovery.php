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
	use TConsumeDeviceAttribute;
	use TConsumeDeviceProperty;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Devices\Attributes\AttributesRepository $attributesRepository,
		private readonly DevicesModels\Devices\Attributes\AttributesManager $attributesManager,
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
		if (!$entity instanceof Entities\Messages\DiscoveredLocalDevice) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		if ($device === null) {
			$findConnectorQuery = new DevicesQueries\FindConnectors();
			$findConnectorQuery->byId($entity->getConnector());

			$connector = $this->connectorsRepository->findOneBy($findConnectorQuery, Entities\ShellyConnector::class);

			if ($connector === null) {
				$this->logger->error(
					'Error during loading connector',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'discovery-message-consumer',
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
					'type' => 'discovery-message-consumer',
					'device' => [
						'id' => $device->getPlainId(),
						'identifier' => $entity->getIdentifier(),
						'address' => $entity->getIpAddress(),
					],
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getDomain(),
			Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN,
		);
		$this->setDeviceProperty(
			$device->getId(),
			strval($entity->getGeneration()->getValue()),
			Types\DevicePropertyIdentifier::IDENTIFIER_GENERATION,
		);
		$this->setDeviceAttribute(
			$device->getId(),
			$entity->getModel(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MODEL,
		);
		$this->setDeviceAttribute(
			$device->getId(),
			$entity->getMacAddress(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_MAC_ADDRESS,
		);
		$this->setDeviceAttribute(
			$device->getId(),
			$entity->getFirmwareVersion(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_VERSION,
		);

		foreach ($entity->getChannels() as $block) {
			$channel = $device->findChannel($block->getIdentifier());

			if ($channel === null) {
				$channel = $this->databaseHelper->transaction(
					fn (): DevicesEntities\Channels\Channel => $this->channelsManager->create(Utils\ArrayHash::from([
						'device' => $device,
						'identifier' => $block->getIdentifier(),
					])),
				);
			}

			foreach ($block->getProperties() as $sensor) {
				$channelProperty = $channel->findProperty($sensor->getIdentifier());

				if ($channelProperty === null) {
					$channelProperty = $this->databaseHelper->transaction(
						fn (): DevicesEntities\Channels\Properties\Property => $this->channelsPropertiesManager->create(
							Utils\ArrayHash::from([
								'channel' => $channel,
								'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
								'identifier' => $sensor->getIdentifier(),
								'unit' => $sensor->getUnit(),
								'dataType' => $sensor->getDataType(),
								'format' => $sensor->getFormat(),
								'invalid' => $sensor->getInvalid(),
								'queryable' => $sensor->isQueryable(),
								'settable' => $sensor->isSettable(),
							]),
						),
					);

					$this->logger->debug(
						'Device sensor was created',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'description-message-consumer',
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
								'unit' => $sensor->getUnit(),
								'dataType' => $sensor->getDataType(),
								'format' => $sensor->getFormat(),
								'invalid' => $sensor->getInvalid(),
								'queryable' => $sensor->isQueryable(),
								'settable' => $sensor->isSettable(),
							]),
						),
					);

					$this->logger->debug(
						'Device sensor was updated',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'description-message-consumer',
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
			'Consumed device found message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
				'type' => 'discovery-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
