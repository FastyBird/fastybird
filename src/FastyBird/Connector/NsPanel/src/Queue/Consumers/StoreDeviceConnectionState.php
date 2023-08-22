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

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;

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
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStatesManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreDeviceConnectionState) {
			return false;
		}

		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\NsPanelDevice::class);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'store-device-connection-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'identifier' => $entity->getIdentifier(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		// Check device state...
		if (
			!$this->deviceConnectionManager->getState($device)->equals($entity->getState())
		) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionManager->setState(
				$device,
				$entity->getState(),
			);

			if (
				$entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_DISCONNECTED)
				|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_LOST)
				|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_ALERT)
				|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_UNKNOWN)
			) {
				$findDevicePropertiesQuery = new DevicesQueries\FindDeviceDynamicProperties();
				$findDevicePropertiesQuery->forDevice($device);

				foreach ($this->devicesPropertiesRepository->findAllBy(
					$findDevicePropertiesQuery,
					DevicesEntities\Devices\Properties\Dynamic::class,
				) as $property) {
					$this->devicePropertiesStatesManager->setValue(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::VALID_KEY => false,
						]),
					);
				}

				$findChannelsQuery = new Queries\FindChannels();
				$findChannelsQuery->forDevice($device);

				$channels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class);

				foreach ($channels as $channel) {
					$findChannelPropertiesQuery = new DevicesQueries\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					foreach ($this->channelsPropertiesRepository->findAllBy(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Dynamic::class,
					) as $property) {
						$this->channelPropertiesStatesManager->setValue(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::VALID_KEY => false,
							]),
						);
					}
				}
			}

			if ($device instanceof Entities\Devices\Gateway) {
				$findChildrenDevicesQuery = new Queries\FindSubDevices();
				$findChildrenDevicesQuery->forParent($device);

				$children = $this->devicesRepository->findAllBy(
					$findChildrenDevicesQuery,
					Entities\Devices\SubDevice::class,
				);

				foreach ($children as $child) {
					$this->deviceConnectionManager->setState(
						$child,
						$entity->getState(),
					);

					if (
						$entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_DISCONNECTED)
						|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_LOST)
						|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_ALERT)
						|| $entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_UNKNOWN)
					) {
						$findDevicePropertiesQuery = new DevicesQueries\FindDeviceDynamicProperties();
						$findDevicePropertiesQuery->forDevice($child);

						foreach ($this->devicesPropertiesRepository->findAllBy(
							$findDevicePropertiesQuery,
							DevicesEntities\Devices\Properties\Dynamic::class,
						) as $property) {
							$this->devicePropertiesStatesManager->setValue(
								$property,
								Utils\ArrayHash::from([
									DevicesStates\Property::VALID_KEY => false,
								]),
							);
						}

						$findChannelsQuery = new Queries\FindChannels();
						$findChannelsQuery->forDevice($child);

						$channels = $this->channelsRepository->findAllBy(
							$findChannelsQuery,
							Entities\NsPanelChannel::class,
						);

						foreach ($channels as $channel) {
							$findChannelPropertiesQuery = new DevicesQueries\FindChannelDynamicProperties();
							$findChannelPropertiesQuery->forChannel($channel);

							foreach ($this->channelsPropertiesRepository->findAllBy(
								$findChannelPropertiesQuery,
								DevicesEntities\Channels\Properties\Dynamic::class,
							) as $property) {
								$this->channelPropertiesStatesManager->setValue(
									$property,
									Utils\ArrayHash::from([
										DevicesStates\Property::VALID_KEY => false,
									]),
								);
							}
						}
					}
				}

				if ($entity->getState()->equalsValue(Metadata\Types\ConnectionState::STATE_ALERT)) {
					$findChildrenDevicesQuery = new Queries\FindThirdPartyDevices();
					$findChildrenDevicesQuery->forParent($device);

					$children = $this->devicesRepository->findAllBy(
						$findChildrenDevicesQuery,
						Entities\Devices\ThirdPartyDevice::class,
					);

					foreach ($children as $child) {
						$this->deviceConnectionManager->setState(
							$child,
							$entity->getState(),
						);
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device online status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'store-device-connection-state-message-consumer',
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
