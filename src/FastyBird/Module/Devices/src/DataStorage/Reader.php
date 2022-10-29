<?php declare(strict_types = 1);

/**
 * Reader.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          0.1.0
 *
 * @date           13.04.19
 */

namespace FastyBird\Module\Devices\DataStorage;

use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Models;
use League\Flysystem;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher;
use Ramsey\Uuid\Uuid;
use function array_key_exists;
use function is_array;
use function is_string;

/**
 * Data storage configuration reader
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     DataStorage
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Reader
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\DataStorage\ConnectorsRepository $connectorsRepository,
		private readonly Models\DataStorage\ConnectorPropertiesRepository $connectorPropertiesRepository,
		private readonly Models\DataStorage\DevicePropertiesRepository $devicePropertiesRepository,
		private readonly Models\DataStorage\ChannelPropertiesRepository $channelPropertiesRepository,
		private readonly Flysystem\Filesystem $filesystem,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher,
	)
	{
	}

	/**
	 * @throws Flysystem\FilesystemException
	 * @throws Utils\JsonException
	 */
	public function read(): void
	{
		try {
			$dataConfiguration = $this->filesystem->read(Devices\Constants::CONFIGURATION_FILE_FILENAME);

		} catch (Flysystem\UnableToReadFile) {
			return;
		}

		$dataConfiguration = Utils\Json::decode($dataConfiguration, Utils\Json::FORCE_ARRAY);

		$this->connectorsRepository->clear();
		$this->connectorPropertiesRepository->clear();
		$this->devicePropertiesRepository->clear();
		$this->channelPropertiesRepository->clear();

		if (!is_array($dataConfiguration)) {
			return;
		}

		foreach ($dataConfiguration as $connectorId => $connectorData) {
			// Validate connector identifier
			if (!is_string($connectorId) || !Uuid::isValid($connectorId)) {
				continue;
			}

			// Validate connector data
			if (
				!is_array($connectorData)
				|| !array_key_exists(Devices\Constants::DATA_STORAGE_PROPERTIES_KEY, $connectorData)
				|| !is_array($connectorData[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY])
				|| !array_key_exists(Devices\Constants::DATA_STORAGE_CONTROLS_KEY, $connectorData)
				|| !is_array($connectorData[Devices\Constants::DATA_STORAGE_CONTROLS_KEY])
				|| !array_key_exists(Devices\Constants::DATA_STORAGE_DEVICES_KEY, $connectorData)
				|| !is_array($connectorData[Devices\Constants::DATA_STORAGE_DEVICES_KEY])
			) {
				continue;
			}

			foreach ($connectorData[Devices\Constants::DATA_STORAGE_DEVICES_KEY] as $deviceId => $deviceData) {
				// Validate device identifier
				if (!is_string($deviceId) || !Uuid::isValid($deviceId)) {
					continue;
				}

				// Validate device data
				if (
					!is_array($deviceData)
					|| !array_key_exists(Devices\Constants::DATA_STORAGE_PROPERTIES_KEY, $deviceData)
					|| !is_array($deviceData[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY])
					|| !array_key_exists(Devices\Constants::DATA_STORAGE_CONTROLS_KEY, $deviceData)
					|| !is_array($deviceData[Devices\Constants::DATA_STORAGE_CONTROLS_KEY])
					|| !array_key_exists(Devices\Constants::DATA_STORAGE_ATTRIBUTES_KEY, $deviceData)
					|| !is_array($deviceData[Devices\Constants::DATA_STORAGE_ATTRIBUTES_KEY])
					|| !array_key_exists(Devices\Constants::DATA_STORAGE_CHANNELS_KEY, $deviceData)
					|| !is_array($deviceData[Devices\Constants::DATA_STORAGE_CHANNELS_KEY])
				) {
					continue;
				}

				foreach ($deviceData[Devices\Constants::DATA_STORAGE_CHANNELS_KEY] as $channelId => $channelData) {
					// Validate channel identifier
					if (!is_string($channelId) || !Uuid::isValid($channelId)) {
						continue;
					}

					// Validate channel data
					if (
						!is_array($channelData)
						|| !array_key_exists(Devices\Constants::DATA_STORAGE_PROPERTIES_KEY, $channelData)
						|| !is_array($channelData[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY])
						|| !array_key_exists(Devices\Constants::DATA_STORAGE_CONTROLS_KEY, $channelData)
						|| !is_array($channelData[Devices\Constants::DATA_STORAGE_CONTROLS_KEY])
					) {
						continue;
					}

					foreach ($channelData[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY] as $propertyId => $propertyData) {
						// Validate channel property identifier
						if (!is_string($propertyId) || !Uuid::isValid($propertyId)) {
							continue;
						}

						// Validate channel property data
						if (!is_array($propertyData)) {
							continue;
						}

						$this->channelPropertiesRepository->append(Uuid::fromString($propertyId), $propertyData);
					}
				}

				foreach ($deviceData[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY] as $propertyId => $propertyData) {
					// Validate device property identifier
					if (!is_string($propertyId) || !Uuid::isValid($propertyId)) {
						continue;
					}

					// Validate device property data
					if (!is_array($propertyData)) {
						continue;
					}

					$this->devicePropertiesRepository->append(Uuid::fromString($propertyId), $propertyData);
				}

				foreach ($deviceData[Devices\Constants::DATA_STORAGE_ATTRIBUTES_KEY] as $attributeId => $attributeData) {
					// Validate device attribute identifier
					if (!is_string($attributeId) || !Uuid::isValid($attributeId)) {
						continue;
					}

					// Validate device attribute data
					if (!is_array($attributeData)) {
						continue;
					}
				}
			}

			foreach ($connectorData[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY] as $propertyId => $propertyData) {
				// Validate connector property identifier
				if (!is_string($propertyId) || !Uuid::isValid($propertyId)) {
					continue;
				}

				// Validate connector property data
				if (!is_array($propertyData)) {
					continue;
				}

				$this->connectorPropertiesRepository->append(Uuid::fromString($propertyId), $propertyData);
			}

			$this->connectorsRepository->append(Uuid::fromString($connectorId), $connectorData);
		}

		$this->dispatcher?->dispatch(new Events\DataStorageRead());
	}

}
