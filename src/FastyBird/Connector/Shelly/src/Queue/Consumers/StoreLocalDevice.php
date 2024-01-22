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
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use function assert;
use function in_array;
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
	use ChannelProperty;

	public function __construct(
		protected readonly Shelly\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		protected readonly ApplicationHelpers\Database $databaseHelper,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidState
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreLocalDevice) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		if ($device === null) {
			$connector = $this->connectorsRepository->find(
				$entity->getConnector(),
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
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_SHELLY,
					'type' => 'store-local-device-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
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
			MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
			Types\DevicePropertyIdentifier::IP_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getDomain(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
			Types\DevicePropertyIdentifier::DOMAIN,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::DOMAIN),
		);
		$this->setDeviceProperty(
			$device->getId(),
			strval($entity->getGeneration()->getValue()),
			MetadataTypes\DataType::get(MetadataTypes\DataType::ENUM),
			Types\DevicePropertyIdentifier::GENERATION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::GENERATION),
			[Types\DeviceGeneration::GENERATION_1, Types\DeviceGeneration::GENERATION_2],
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->isAuthEnabled(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::BOOLEAN),
			Types\DevicePropertyIdentifier::AUTH_ENABLED,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::AUTH_ENABLED),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getModel(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
			Types\DevicePropertyIdentifier::MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MODEL),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getMacAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
			Types\DevicePropertyIdentifier::MAC_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getFirmwareVersion(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
			Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::FIRMWARE_VERSION),
		);

		foreach ($entity->getChannels() as $channelDescription) {
			$findChannelQuery = new Queries\Entities\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($channelDescription->getIdentifier());

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\ShellyChannel::class);

			$channel = $channel === null ? $this->databaseHelper->transaction(
				fn (): DevicesEntities\Channels\Channel => $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\ShellyChannel::class,
					'device' => $device,
					'identifier' => $channelDescription->getIdentifier(),
					'name' => $channelDescription->getName(),
				])),
			) : $this->databaseHelper->transaction(
				fn (): DevicesEntities\Channels\Channel => $this->channelsManager->update(
					$channel,
					Utils\ArrayHash::from([
						'name' => $channelDescription->getName(),
					]),
				),
			);

			$propertiesIdentifiers = [];

			foreach ($channelDescription->getProperties() as $propertyDescription) {
				$this->setChannelProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					$channel->getId(),
					null,
					$propertyDescription->getDataType(),
					$propertyDescription->getIdentifier(),
					$propertyDescription->getName(),
					$propertyDescription->getFormat(),
					$propertyDescription->getUnit(),
					$propertyDescription->getInvalid(),
					$propertyDescription->isSettable(),
					$propertyDescription->isQueryable(),
				);

				$propertiesIdentifiers[] = $propertyDescription->getIdentifier();
			}

			$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			$properties = $this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery);

			foreach ($properties as $property) {
				if (!in_array($property->getIdentifier(), $propertiesIdentifiers, true)) {
					$this->channelsPropertiesManager->delete($property);
				}
			}
		}

		$this->logger->debug(
			'Consumed store device message',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_SHELLY,
				'type' => 'store-local-device-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
