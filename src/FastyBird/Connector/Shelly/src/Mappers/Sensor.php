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
use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
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

	/** @var Array<string, Uuid\UuidInterface> */
	private array $sensorsToProperties = [];

	public function __construct(
		private readonly DevicesModuleModels\DataStorage\DevicesRepository $devicesRepository,
		private readonly DevicesModuleModels\DataStorage\ChannelsRepository $channelsRepository,
		private readonly DevicesModuleModels\DataStorage\ChannelPropertiesRepository $channelPropertiesRepository,
	)
	{
	}

	/**
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function findProperty(
		Uuid\UuidInterface $connector,
		string $deviceIdentifier,
		int $sensorIdentifier,
	): MetadataEntities\DevicesModule\ChannelDynamicProperty|null
	{
		$key = $deviceIdentifier . '-' . $sensorIdentifier;

		if (array_key_exists($key, $this->sensorsToProperties)) {
			$property = $this->channelPropertiesRepository->findById($this->sensorsToProperties[$key]);

			if ($property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty) {
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
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function loadProperty(
		Uuid\UuidInterface $connector,
		string $deviceIdentifier,
		int $sensorIdentifier,
	): MetadataEntities\DevicesModule\ChannelDynamicProperty|null
	{
		$device = $this->devicesRepository->findByIdentifier($connector, $deviceIdentifier);

		if ($device === null) {
			return null;
		}

		$channels = $this->channelsRepository->findAllByDevice($device->getId());

		foreach ($channels as $channel) {
			$properties = $this->channelPropertiesRepository->findAllByChannel($channel->getId());

			foreach ($properties as $property) {
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
					if ($property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty) {
						return $property;
					}

					return null;
				}
			}
		}

		return null;
	}

}
