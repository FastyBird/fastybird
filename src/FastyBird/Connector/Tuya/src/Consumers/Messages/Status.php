<?php declare(strict_types = 1);

/**
 * Status.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Tuya\Consumers\Messages;

use FastyBird\Connector\Tuya\Consumers\Consumer;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Mappers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use Psr\Log;

/**
 * Device status message consumer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Status implements Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly Mappers\DataPoint $dataPointMapper,
		private readonly Helpers\Property $propertyStateHelper,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
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
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\TuyaDevice::class);

		if ($device === null) {
			return true;
		}

		foreach ($entity->getDataPoints() as $dataPoint) {
			$property = $this->dataPointMapper->findProperty(
				$entity->getConnector(),
				$entity->getIdentifier(),
				$dataPoint->getCode(),
			);

			if ($property !== null) {
				$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_KEY => $dataPoint->getValue(),
					DevicesStates\Property::VALID_KEY => true,
				]));
			}
		}

		$this->logger->debug(
			'Consumed device status message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
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
