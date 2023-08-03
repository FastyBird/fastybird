<?php declare(strict_types = 1);

/**
 * DeviceStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           18.07.23
 */

namespace FastyBird\Connector\NsPanel\Consumers\Messages;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Consumers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;

/**
 * Device status message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceStatus implements Consumers\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly NsPanel\Logger $logger,
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
		if (!$entity instanceof Entities\Messages\DeviceStatus) {
			return false;
		}

		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\NsPanelDevice::class);

		if ($device === null) {
			return true;
		}

		foreach ($entity->getStatuses() as $status) {
			$findChannelQuery = new Queries\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byId($status->getChanel());

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\NsPanelChannel::class);

			if ($channel !== null) {
				$findChannelPropertyQuery = new DevicesQueries\FindChannelDynamicProperties();
				$findChannelPropertyQuery->forChannel($channel);
				$findChannelPropertyQuery->byId($status->getProperty());

				$property = $this->channelPropertiesRepository->findOneBy(
					$findChannelPropertyQuery,
					DevicesEntities\Channels\Properties\Dynamic::class,
				);

				if ($property !== null) {
					$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_KEY => $status->getValue(),
						DevicesStates\Property::VALID_KEY => true,
					]));
				}
			}
		}

		$this->logger->debug(
			'Consumed device status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'device-status-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
