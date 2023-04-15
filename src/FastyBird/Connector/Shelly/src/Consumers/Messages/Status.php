<?php declare(strict_types = 1);

/**
 * Status.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\Shelly\Consumers\Consumer;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Types;
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
use Psr\Log;

/**
 * Device status message consumer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Status implements Consumer
{

	use Nette\SmartObject;
	use ConsumeDeviceProperty;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\Database $databaseHelper,
		private readonly Helpers\Property $propertyStateHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
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
		if (!$entity instanceof Entities\Messages\DeviceStatus) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->startWithIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		if ($device === null) {
			return true;
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
		);

		foreach ($entity->getStatuses() as $status) {
			if ($status instanceof Entities\Messages\PropertyStatus) {
				$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);
				$findDevicePropertyQuery->byIdentifier($status->getIdentifier());

				$property = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

				if ($property === null) {
					$findChannelsQuery = new DevicesQueries\FindChannels();
					$findChannelsQuery->forDevice($device);

					$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

					foreach ($channels as $channel) {
						$property = $channel->findProperty($status->getIdentifier());

						if ($property !== null) {
							break;
						}
					}
				}

				if (
					$property instanceof DevicesEntities\Devices\Properties\Dynamic
					|| $property instanceof DevicesEntities\Channels\Properties\Dynamic
				) {
					$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_KEY => $status->getValue(),
						DevicesStates\Property::VALID_KEY => true,
					]));
				}
			} else {
				$findChannelQuery = new DevicesQueries\FindChannels();
				$findChannelQuery->forDevice($device);
				$findChannelQuery->byIdentifier($status->getIdentifier());

				$channel = $this->channelsRepository->findOneBy($findChannelQuery);

				if ($channel !== null) {
					foreach ($status->getSensors() as $sensor) {
						$property = $channel->findProperty($sensor->getIdentifier());

						if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
							$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
								DevicesStates\Property::ACTUAL_VALUE_KEY => $sensor->getValue(),
								DevicesStates\Property::VALID_KEY => true,
							]));
						} elseif ($property instanceof DevicesEntities\Channels\Properties\Variable) {
							$this->databaseHelper->transaction(
								function () use ($property, $sensor): void {
									$this->channelsPropertiesManager->update(
										$property,
										Utils\ArrayHash::from([
											'value' => $sensor->getValue(),
										]),
									);
								},
							);
						}
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
				'type' => 'status-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
