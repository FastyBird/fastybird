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
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
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
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		if ($device === null) {
			return true;
		}

		// Check device state...
		if (
			!$this->deviceConnectionManager->getState($device)
				->equalsValue(MetadataTypes\ConnectionState::STATE_CONNECTED)
		) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionManager->setState(
				$device,
				MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_CONNECTED),
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
		);

		foreach ($entity->getStatuses() as $status) {
			if ($status instanceof Entities\Messages\PropertyStatus) {
				$property = null;

				$property = $device->findProperty($status->getIdentifier());

				if ($property === null) {
					foreach ($device->getChannels() as $channel) {
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
					$actualValue = DevicesUtilities\ValueHelper::flattenValue(
						DevicesUtilities\ValueHelper::normalizeValue(
							$property->getDataType(),
							$status->getValue(),
							$property->getFormat(),
							$property->getInvalid(),
						),
					);

					$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_KEY => $actualValue,
						DevicesStates\Property::VALID_KEY => true,
					]));
				}
			} else {
				$channel = $device->findChannel($status->getIdentifier());

				if ($channel !== null) {
					foreach ($status->getSensors() as $sensor) {
						$property = $channel->findProperty($sensor->getIdentifier());

						if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
							$actualValue = DevicesUtilities\ValueHelper::flattenValue(
								DevicesUtilities\ValueHelper::normalizeValue(
									$property->getDataType(),
									$sensor->getValue(),
									$property->getFormat(),
									$property->getInvalid(),
								),
							);

							$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
								DevicesStates\Property::ACTUAL_VALUE_KEY => $actualValue,
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
