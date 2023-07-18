<?php declare(strict_types = 1);

/**
 * Status.php
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
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use function assert;
use function strval;

/**
 * Device status message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Status implements Consumers\Consumer
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

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->startWithIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\NsPanelDevice::class);
		assert($device instanceof Entities\NsPanelDevice || $device === null);

		if ($device === null) {
			return true;
		}

		foreach ($entity->getStatuses() as $status) {
			$property = $this->findProperty($device, $status);

			if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
				$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_KEY => $status->getValue(),
					DevicesStates\Property::VALID_KEY => true,
				]));
			}
		}

		$this->logger->debug(
			'Consumed device status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'status-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function findProperty(
		Entities\NsPanelDevice $device,
		Entities\Messages\CapabilityStatus $status,
	): DevicesEntities\Channels\Properties\Dynamic|null
	{
		if ($status->getName() !== null) {
			$findChannelQuery = new DevicesQueries\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($status->getName());

			$channel = $this->channelsRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				return null;
			}

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(strval($status->getCapability()->getValue()));

			$property = $this->channelPropertiesRepository->findOneBy(
				$findChannelPropertyQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);
			assert($property instanceof DevicesEntities\Channels\Properties\Dynamic || $property === null);

			return $property;
		} else {
			$findChannelsQuery = new DevicesQueries\FindChannels();
			$findChannelsQuery->forDevice($device);

			foreach ($this->channelsRepository->findAllBy($findChannelsQuery) as $channel) {
				$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
				$findChannelPropertyQuery->forChannel($channel);
				$findChannelPropertyQuery->byIdentifier(strval($status->getCapability()->getValue()));

				$property = $this->channelPropertiesRepository->findOneBy(
					$findChannelPropertyQuery,
					DevicesEntities\Channels\Properties\Dynamic::class,
				);
				assert($property instanceof DevicesEntities\Channels\Properties\Dynamic || $property === null);

				if ($property !== null) {
					return $property;
				}
			}
		}

		return null;
	}

}
