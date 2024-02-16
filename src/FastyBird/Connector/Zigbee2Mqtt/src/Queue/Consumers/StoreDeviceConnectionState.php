<?php declare(strict_types = 1);

/**
 * SetDeviceConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           31.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use TypeError;
use ValueError;

/**
 * Store device connection state message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceConnectionState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Zigbee2Mqtt\Helpers\Devices\Bridge $bridgeHelper,
		private readonly Zigbee2Mqtt\Helpers\Devices\SubDevice $subDeviceHelper,
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
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreDeviceConnectionState) {
			return false;
		}

		$findDeviceQuery = new Queries\Configuration\FindSubDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\SubDevice::class,
		);

		if ($device === null) {
			return true;
		}

		$bridge = $this->subDeviceHelper->getBridge($device);

		if ($this->bridgeHelper->getBaseTopic($bridge) !== $message->getBaseTopic()) {
			return true;
		}

		$state = DevicesTypes\ConnectionState::UNKNOWN;

		if ($message->getState()->equalsValue(Types\ConnectionState::ONLINE)) {
			$state = DevicesTypes\ConnectionState::CONNECTED;
		} elseif ($message->getState()->equalsValue(Types\ConnectionState::OFFLINE)) {
			$state = DevicesTypes\ConnectionState::DISCONNECTED;
		} elseif ($message->getState()->equalsValue(Types\ConnectionState::ALERT)) {
			$state = DevicesTypes\ConnectionState::ALERT;
		}

		// Check device state...
		if ($this->deviceConnectionManager->getState($device) !== $state) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionManager->setState($device, $state);

			if (
				$state === DevicesTypes\ConnectionState::DISCONNECTED
				|| $state === DevicesTypes\ConnectionState::ALERT
				|| $state === DevicesTypes\ConnectionState::UNKNOWN
			) {
				$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
				$findDevicePropertiesQuery->forDevice($device);

				$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
					$findDevicePropertiesQuery,
					DevicesDocuments\Devices\Properties\Dynamic::class,
				);

				foreach ($properties as $property) {
					$this->devicePropertiesStatesManager->setValidState(
						$property,
						false,
						MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::ZIGBEE2MQTT),
					);
				}

				$findChannelsQuery = new Queries\Configuration\FindChannels();
				$findChannelsQuery->forDevice($device);

				$channels = $this->channelsConfigurationRepository->findAllBy(
					$findChannelsQuery,
					Documents\Channels\Channel::class,
				);

				foreach ($channels as $channel) {
					$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findChannelPropertiesQuery,
						DevicesDocuments\Channels\Properties\Dynamic::class,
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

		$this->logger->debug(
			'Consumed device connection state message',
			[
				'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
				'type' => 'store-device-connection-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'bridge' => [
					'id' => $bridge->getId()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
