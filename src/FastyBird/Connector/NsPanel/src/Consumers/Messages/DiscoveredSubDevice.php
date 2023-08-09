<?php declare(strict_types = 1);

/**
 * DiscoveredSubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\NsPanel\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Consumers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function assert;
use function is_array;

/**
 * NS Panel sub-devices discovery message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredSubDevice implements Consumers\Consumer
{

	use ConsumeDeviceProperty;
	use Nette\SmartObject;

	public function __construct(
		protected readonly DevicesModels\Devices\DevicesManager $devicesManager,
		protected readonly DevicesModels\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesUtilities\Database $databaseHelper,
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly NsPanel\Logger $logger,
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
		if (!$entity instanceof Entities\Messages\DiscoveredSubDevice) {
			return false;
		}

		$findDeviceQuery = new Queries\FindGatewayDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byId($entity->getGateway());

		$parent = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Gateway::class);

		if ($parent === null) {
			return true;
		}

		$findDeviceQuery = new Queries\FindSubDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->forParent($parent);
		$findDeviceQuery->byIdentifier($entity->getSerialNumber());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\SubDevice::class);

		if ($device === null) {
			$findConnectorQuery = new Queries\FindConnectors();
			$findConnectorQuery->byId($entity->getConnector());

			$connector = $this->connectorsRepository->findOneBy(
				$findConnectorQuery,
				Entities\NsPanelConnector::class,
			);

			if ($connector === null) {
				return true;
			}

			$device = $this->databaseHelper->transaction(
				function () use ($entity, $connector, $parent): Entities\Devices\SubDevice {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Devices\SubDevice::class,
						'connector' => $connector,
						'parent' => $parent,
						'identifier' => $entity->getSerialNumber(),
						'name' => $entity->getName(),
					]));
					assert($device instanceof Entities\Devices\SubDevice);

					return $device;
				},
			);

			$this->logger->info(
				'Creating new sub-device',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'discovered-sub-device-message-consumer',
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $entity->getSerialNumber(),
						'protocol' => $entity->getProtocol(),
					],
				],
			);
		} else {
			$device = $this->databaseHelper->transaction(
				function () use ($entity, $device): Entities\Devices\SubDevice {
					$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
						'name' => $entity->getName(),
					]));
					assert($device instanceof Entities\Devices\SubDevice);

					return $device;
				},
			);

			$this->logger->debug(
				'Sub-device was updated',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'discovered-sub-device-message-consumer',
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $entity->getSerialNumber(),
						'protocol' => $entity->getProtocol(),
					],
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getManufacturer(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::MANUFACTURER,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::MANUFACTURER),
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
			$entity->getFirmwareVersion(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::FIRMWARE_VERSION),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getDisplayCategory()->getValue(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::CATEGORY,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::CATEGORY),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$entity->getMacAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::MAC_ADDRESS,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS),
		);

		foreach ($entity->getCapabilities() as $capability) {
			$this->databaseHelper->transaction(function () use ($device, $capability): bool {
				$identifier = Helpers\Name::convertCapabilityToChannel($capability->getCapability());

				if (
					$capability->getCapability()->equalsValue(Types\Capability::TOGGLE)
					&& $capability->getName() !== null
				) {
					$identifier .= '_' . $capability->getName();
				}

				$findChannelQuery = new Queries\FindChannels();
				$findChannelQuery->byIdentifier($identifier);
				$findChannelQuery->forDevice($device);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\NsPanelChannel::class);

				if ($channel === null) {
					$channel = $this->channelsManager->create(Utils\ArrayHash::from([
						'entity' => Entities\NsPanelChannel::class,
						'device' => $device,
						'identifier' => $identifier,
					]));

					$this->logger->debug(
						'Creating new device channel',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
							'type' => 'discovered-sub-device-message-consumer',
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'channel' => [
								'id' => $channel->getId()->toString(),
							],
						],
					);
				}

				return true;
			});
		}

		foreach ($entity->getTags() as $tag => $value) {
			if ($tag === Types\Capability::TOGGLE && is_array($value)) {
				$this->databaseHelper->transaction(function () use ($device, $value): void {
					foreach ($value as $key => $name) {
						$findChannelQuery = new Queries\FindChannels();
						$findChannelQuery->byIdentifier(
							Helpers\Name::convertCapabilityToChannel(
								Types\Capability::get(Types\Capability::TOGGLE),
								$key,
							),
						);
						$findChannelQuery->forDevice($device);

						$channel = $this->channelsRepository->findOneBy(
							$findChannelQuery,
							Entities\NsPanelChannel::class,
						);

						if ($channel !== null) {
							$channel = $this->channelsManager->update($channel, Utils\ArrayHash::from([
								'name' => $name,
							]));

							$this->logger->debug(
								'Set toggle channel name',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
									'type' => 'discovered-sub-device-message-consumer',
									'device' => [
										'id' => $device->getId()->toString(),
									],
									'channel' => [
										'id' => $channel->getId()->toString(),
									],
								],
							);
						}
					}
				});
			}
		}

		$this->logger->debug(
			'Consumed sub-device found message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'discovered-sub-device-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
