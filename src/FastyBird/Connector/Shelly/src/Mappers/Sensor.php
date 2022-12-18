<?php declare(strict_types = 1);

/**
 * Sensor.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Mappers
 * @since          0.37.0
 *
 * @date           21.07.22
 */

namespace FastyBird\Connector\Shelly\Mappers;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Ramsey\Uuid;
use function array_key_exists;
use function intval;
use function preg_match;

/**
 * Device sensor to module property mapper
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Mappers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Sensor
{

	use Nette\SmartObject;

	/** @var array<string, Uuid\UuidInterface> */
	private array $sensorsToProperties = [];

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
		int $sensorIdentifier,
	): DevicesEntities\Channels\Properties\Dynamic|null
	{
		$key = $deviceIdentifier . '-' . $sensorIdentifier;

		if (array_key_exists($key, $this->sensorsToProperties)) {
			$findPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findPropertyQuery->byId($this->sensorsToProperties[$key]);

			$property = $this->channelPropertiesRepository->findOneBy(
				$findPropertyQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);

			if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
				return $property;
			}
		}

		$property = $this->loadProperty($connector, $deviceIdentifier, $sensorIdentifier);

		if ($property !== null) {
			$this->sensorsToProperties[$key] = $property->getId();
		}

		return $property;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function loadProperty(
		Uuid\UuidInterface $connector,
		string $deviceIdentifier,
		int $sensorIdentifier,
	): DevicesEntities\Channels\Properties\Dynamic|null
	{
		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($connector);
		$findDeviceQuery->byIdentifier($deviceIdentifier);

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		if ($device === null) {
			return null;
		}

		foreach ($device->getChannels() as $channel) {
			foreach ($channel->getProperties() as $property) {
				if (
					preg_match(
						Shelly\Constants::GEN_1_PROPERTY_SENSOR,
						$property->getIdentifier(),
						$propertyMatches,
					) === 1
					&& array_key_exists('identifier', $propertyMatches)
					&& array_key_exists('type', $propertyMatches)
					&& array_key_exists('description', $propertyMatches)
					&& intval($propertyMatches['identifier']) === $sensorIdentifier
				) {
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
