<?php declare(strict_types = 1);

/**
 * SetDeviceConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Queue;
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
 * Store device connection state message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceConnectionState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly NsPanel\Logger $logger,
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
		if (!$message instanceof Queue\Messages\StoreDeviceConnectionState) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getIdentifier());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL,
					'type' => 'store-device-connection-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'identifier' => $message->getIdentifier(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		// Check device state...
		if (
			!$this->deviceConnectionManager->getState($device)->equals($message->getState())
		) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionManager->setState(
				$device,
				$message->getState(),
			);

			if (
				$message->getState()->equalsValue(MetadataTypes\ConnectionState::DISCONNECTED)
				|| $message->getState()->equalsValue(MetadataTypes\ConnectionState::ALERT)
				|| $message->getState()->equalsValue(MetadataTypes\ConnectionState::UNKNOWN)
			) {
				$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
				$findDevicePropertiesQuery->forDevice($device);

				$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
					$findDevicePropertiesQuery,
					MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
				);

				foreach ($properties as $property) {
					$this->devicePropertiesStatesManager->setValidState(
						$property,
						false,
						MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::NS_PANEL),
					);
				}

				$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
				$findChannelsQuery->forDevice($device);

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
							MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::NS_PANEL),
						);
					}
				}
			}

			if ($device->getType() === Entities\Devices\Gateway::TYPE) {
				if (
					$message->getState()->equalsValue(MetadataTypes\ConnectionState::DISCONNECTED)
					|| $message->getState()->equalsValue(MetadataTypes\ConnectionState::ALERT)
					|| $message->getState()->equalsValue(MetadataTypes\ConnectionState::UNKNOWN)
				) {
					$findChildrenDevicesQuery = new DevicesQueries\Configuration\FindDevices();
					$findChildrenDevicesQuery->forParent($device);
					$findChildrenDevicesQuery->byType(Entities\Devices\SubDevice::TYPE);

					$children = $this->devicesConfigurationRepository->findAllBy($findChildrenDevicesQuery);

					foreach ($children as $child) {
						$this->deviceConnectionManager->setState(
							$child,
							$message->getState(),
						);

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
								MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::NS_PANEL),
							);
						}

						$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
						$findChannelsQuery->forDevice($child);

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
									MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::NS_PANEL),
								);
							}
						}
					}
				}

				if ($message->getState()->equalsValue(MetadataTypes\ConnectionState::ALERT)) {
					$findChildrenDevicesQuery = new DevicesQueries\Configuration\FindDevices();
					$findChildrenDevicesQuery->forParent($device);
					$findChildrenDevicesQuery->byType(Entities\Devices\ThirdPartyDevice::TYPE);

					$children = $this->devicesConfigurationRepository->findAllBy($findChildrenDevicesQuery);

					foreach ($children as $child) {
						$this->deviceConnectionManager->setState(
							$child,
							$message->getState(),
						);
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device connection state message',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL,
				'type' => 'store-device-connection-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
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
