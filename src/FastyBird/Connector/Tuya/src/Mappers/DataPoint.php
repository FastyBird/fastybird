<?php declare(strict_types = 1);

/**
 * DataPoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Mappers
 * @since          0.13.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Tuya\Mappers;

use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
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

	/** @var Array<string, Uuid\UuidInterface> */
	private array $dataPointsToProperties = [];

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
		string $dataPointIdentifier,
	): MetadataEntities\DevicesModule\ChannelDynamicProperty|null
	{
		$key = $deviceIdentifier . '-' . $dataPointIdentifier;

		if (array_key_exists($key, $this->dataPointsToProperties)) {
			$property = $this->channelPropertiesRepository->findById($this->dataPointsToProperties[$key]);

			if ($property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty) {
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
		string $dataPointIdentifier,
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
				if ($property->getIdentifier() === $dataPointIdentifier) {
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
