<?php declare(strict_types = 1);

/**
 * DataPoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Mappers
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Tuya\Mappers;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Ramsey\Uuid;
use function array_key_exists;

/**
 * Device data point to module property mapper
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Mappers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DataPoint
{

	use Nette\SmartObject;

	/** @var array<string, Uuid\UuidInterface> */
	private array $dataPointsToProperties = [];

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function findProperty(
		Uuid\UuidInterface $connector,
		string $deviceIdentifier,
		string $dataPointIdentifier,
	): DevicesEntities\Channels\Properties\Dynamic|null
	{
		$key = $deviceIdentifier . '-' . $dataPointIdentifier;

		if (array_key_exists($key, $this->dataPointsToProperties)) {
			$findPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findPropertyQuery->byId($this->dataPointsToProperties[$key]);

			$property = $this->channelPropertiesRepository->findOneBy(
				$findPropertyQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);

			if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
				return $property;
			}
		}

		$property = $this->loadProperty($connector, $deviceIdentifier, $dataPointIdentifier);

		if ($property !== null) {
			$this->dataPointsToProperties[$key] = $property->getId();
		}

		return $property;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function loadProperty(
		Uuid\UuidInterface $connector,
		string $deviceIdentifier,
		string $dataPointIdentifier,
	): DevicesEntities\Channels\Properties\Dynamic|null
	{
		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($connector);
		$findDeviceQuery->byIdentifier($deviceIdentifier);

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\TuyaDevice::class);

		if ($device === null) {
			return null;
		}

		foreach ($device->getChannels() as $channel) {
			foreach ($channel->getProperties() as $property) {
				if ($property->getIdentifier() === $dataPointIdentifier) {
					if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
						return $property;
					}

					return null;
				}
			}
		}

		return null;
	}

}
