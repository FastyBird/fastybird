<?php declare(strict_types = 1);

/**
 * StoreBridgeConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;

/**
 * Store bridge connection state message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeConnectionState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesModels\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreBridgeConnectionState) {
			return false;
		}

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::BASE_TOPIC);
		$findDevicePropertyQuery->byValue($entity->getBaseTopic());

		$baseTopicProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($baseTopicProperty === null) {
			return true;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byId($baseTopicProperty->getDevice());
		$findDeviceQuery->byType(Entities\Devices\Bridge::TYPE);

		$bridge = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($bridge === null) {
			return true;
		}

		$state = MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::UNKNOWN);

		if ($entity->getState()->equalsValue(Types\ConnectionState::ONLINE)) {
			$state = MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::CONNECTED);
		} elseif ($entity->getState()->equalsValue(Types\ConnectionState::OFFLINE)) {
			$state = MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::DISCONNECTED);
		} elseif ($entity->getState()->equalsValue(Types\ConnectionState::ALERT)) {
			$state = MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::ALERT);
		}

		// Check device state...
		if (
			!$this->deviceConnectionManager->getState($bridge)->equals($state)
		) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionManager->setState($bridge, $state);

			if (
				$state->equalsValue(MetadataTypes\ConnectionState::DISCONNECTED)
				|| $state->equalsValue(MetadataTypes\ConnectionState::ALERT)
				|| $state->equalsValue(MetadataTypes\ConnectionState::UNKNOWN)
			) {
				$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
				$findDevicePropertiesQuery->forDevice($bridge);

				$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
					$findDevicePropertiesQuery,
					MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
				);

				foreach ($properties as $property) {
					$this->devicePropertiesStatesManager->setValidState($property, false);
				}

				$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
				$findChannelsQuery->forDevice($bridge);
				$findChannelsQuery->byType(Entities\Zigbee2MqttChannel::TYPE);

				$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

				foreach ($channels as $channel) {
					$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findChannelPropertiesQuery,
						MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
					);

					foreach ($properties as $property) {
						$this->channelPropertiesStatesManager->setValidState($property, false);
					}
				}

				$findChildrenDevicesQuery = new DevicesQueries\Configuration\FindDevices();
				$findChildrenDevicesQuery->forParent($bridge);
				$findChildrenDevicesQuery->byType(Entities\Devices\SubDevice::TYPE);

				$children = $this->devicesConfigurationRepository->findAllBy($findChildrenDevicesQuery);

				foreach ($children as $child) {
					$this->deviceConnectionManager->setState($child, $state);

					$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
					$findDevicePropertiesQuery->forDevice($child);

					$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
						$findDevicePropertiesQuery,
						MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
					);

					foreach ($properties as $property) {
						$this->devicePropertiesStatesManager->setValidState($property, false);
					}

					$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
					$findChannelsQuery->forDevice($child);
					$findChannelsQuery->byType(Entities\Zigbee2MqttChannel::TYPE);

					$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

					foreach ($channels as $channel) {
						$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
						$findChannelPropertiesQuery->forChannel($channel);

						$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
							$findChannelPropertiesQuery,
							MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
						);

						foreach ($properties as $property) {
							$this->channelPropertiesStatesManager->setValidState($property, false);
						}
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed bridge connection state message',
			[
				'source' => MetadataTypes\ConnectorSource::CONNECTOR_ZIGBEE2MQTT,
				'type' => 'store-bridge-connection-state-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
