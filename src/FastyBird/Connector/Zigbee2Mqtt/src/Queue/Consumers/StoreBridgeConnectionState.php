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
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
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
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreBridgeConnectionState) {
			return false;
		}

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::BASE_TOPIC);
		$findDevicePropertyQuery->byValue($message->getBaseTopic());

		$baseTopicProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($baseTopicProperty === null) {
			return true;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($baseTopicProperty->getDevice());
		$findDeviceQuery->byType(Entities\Devices\Bridge::TYPE);

		$bridge = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($bridge === null) {
			return true;
		}

		$state = MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::UNKNOWN);

		if ($message->getState()->equalsValue(Types\ConnectionState::ONLINE)) {
			$state = MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::CONNECTED);
		} elseif ($message->getState()->equalsValue(Types\ConnectionState::OFFLINE)) {
			$state = MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::DISCONNECTED);
		} elseif ($message->getState()->equalsValue(Types\ConnectionState::ALERT)) {
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
					$this->devicePropertiesStatesManager->setValidState(
						$property,
						false,
						MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::ZIGBEE2MQTT),
					);
				}

				$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
				$findChannelsQuery->forDevice($bridge);
				$findChannelsQuery->byType(Entities\Channels\Channel::TYPE);

				$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

				foreach ($channels as $channel) {
					$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findChannelPropertiesQuery,
						MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
					);

					foreach ($properties as $property) {
						$this->channelPropertiesStatesManager->setValidState(
							$property,
							false,
							MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::ZIGBEE2MQTT),
						);
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
						$this->devicePropertiesStatesManager->setValidState(
							$property,
							false,
							MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::ZIGBEE2MQTT),
						);
					}

					$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
					$findChannelsQuery->forDevice($child);
					$findChannelsQuery->byType(Entities\Channels\Channel::TYPE);

					$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

					foreach ($channels as $channel) {
						$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
						$findChannelPropertiesQuery->forChannel($channel);

						$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
							$findChannelPropertiesQuery,
							MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
						);

						foreach ($properties as $property) {
							$this->channelPropertiesStatesManager->setValidState(
								$property,
								false,
								MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::ZIGBEE2MQTT),
							);
						}
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed bridge connection state message',
			[
				'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
				'type' => 'store-bridge-connection-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'bridge' => [
					'id' => $bridge->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
