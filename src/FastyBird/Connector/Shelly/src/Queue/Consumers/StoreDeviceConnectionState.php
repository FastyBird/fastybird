<?php declare(strict_types = 1);

/**
 * StoreDeviceConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           12.01.23
 */

namespace FastyBird\Connector\Shelly\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;

/**
 * Store device connection state message consumer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceConnectionState implements Queue\Consumer
{

	use Nette\SmartObject;

	/**
	 * @param DevicesModels\Configuration\Devices\Repository<Metadata\Documents\DevicesModule\Device> $devicesRepository
	 * @param DevicesModels\Configuration\Devices\Properties\Repository<Metadata\Documents\DevicesModule\DeviceDynamicProperty> $devicesPropertiesRepository
	 * @param DevicesModels\Configuration\Channels\Repository<Metadata\Documents\DevicesModule\Channel> $channelsRepository
	 * @param DevicesModels\Configuration\Channels\Properties\Repository<Metadata\Documents\DevicesModule\ChannelDynamicProperty> $channelsPropertiesRepository
	 */
	public function __construct(
		private readonly Shelly\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStateManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStateManager,
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
		if (!$entity instanceof Entities\Messages\StoreDeviceConnectionState) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->startWithIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
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
				$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
				$findDevicePropertiesQuery->forDevice($device);

				foreach ($this->devicesPropertiesRepository->findAllBy(
					$findDevicePropertiesQuery,
					Metadata\Documents\DevicesModule\DeviceDynamicProperty::class,
				) as $property) {
					$this->devicePropertiesStateManager->setValidState($property, false);
				}

				$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
				$findChannelsQuery->forDevice($device);

				$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

				foreach ($channels as $channel) {
					$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					foreach ($this->channelsPropertiesRepository->findAllBy(
						$findChannelPropertiesQuery,
						Metadata\Documents\DevicesModule\ChannelDynamicProperty::class,
					) as $property) {
						$this->channelPropertiesStateManager->setValidState($property, false);
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device connection status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
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
