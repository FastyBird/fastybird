<?php declare(strict_types = 1);

/**
 * Writer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     DataStorage
 * @since          0.1.0
 *
 * @date           13.04.19
 */

namespace FastyBird\Module\Devices\DataStorage;

use Exception;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use League\Flysystem;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher;
use function array_merge;

/**
 * Data storage configuration writer
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     DataStorage
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Writer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\Connectors\Properties\PropertiesRepository $connectorsPropertiesRepository,
		private readonly Models\Connectors\Controls\ControlsRepository $connectorsControlsRepository,
		private readonly Models\Devices\DevicesRepository $devicesRepository,
		private readonly Models\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly Models\Devices\Controls\ControlsRepository $devicesControlsRepository,
		private readonly Models\Devices\Attributes\AttributesRepository $devicesAttributesRepository,
		private readonly Models\Channels\ChannelsRepository $channelsRepository,
		private readonly Models\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly Models\Channels\Controls\ControlsRepository $channelsControlsRepository,
		private readonly Reader $reader,
		private readonly Flysystem\Filesystem $filesystem,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher,
	)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws Flysystem\FilesystemException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	public function write(): void
	{
		$data = [];

		$findConnectorsQuery = new Queries\FindConnectors();

		$connectors = $this->connectorsRepository->findAllBy($findConnectorsQuery);

		foreach ($connectors as $connector) {
			$devices = [];

			$findDevicesQuery = new Queries\FindDevices();
			$findDevicesQuery->forConnector($connector);

			foreach ($this->devicesRepository->findAllBy($findDevicesQuery) as $device) {
				$channels = [];

				$findChannelsQuery = new Queries\FindChannels();
				$findChannelsQuery->forDevice($device);

				foreach ($this->channelsRepository->findAllBy($findChannelsQuery) as $channel) {
					$properties = [];

					$findChannelPropertiesQuery = new Queries\FindChannelProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					foreach ($this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery) as $property) {
						$properties[$property->getPlainId()] = $property->toArray();
					}

					$controls = [];

					$findChannelControlsQuery = new Queries\FindChannelControls();
					$findChannelControlsQuery->forChannel($channel);

					foreach ($this->channelsControlsRepository->findAllBy($findChannelControlsQuery) as $control) {
						$controls[$control->getPlainId()] = $control->toArray();
					}

					$channels[$channel->getPlainId()] = array_merge($channel->toArray(), [
						Devices\Constants::DATA_STORAGE_PROPERTIES_KEY => $properties,
						Devices\Constants::DATA_STORAGE_CONTROLS_KEY => $controls,
					]);
				}

				$properties = [];

				$findDevicePropertiesQuery = new Queries\FindDeviceProperties();
				$findDevicePropertiesQuery->forDevice($device);

				foreach ($this->devicesPropertiesRepository->findAllBy($findDevicePropertiesQuery) as $property) {
					$properties[$property->getPlainId()] = $property->toArray();
				}

				$attributes = [];

				$findDeviceAttributesQuery = new Queries\FindDeviceAttributes();
				$findDeviceAttributesQuery->forDevice($device);

				foreach ($this->devicesAttributesRepository->findAllBy($findDeviceAttributesQuery) as $attribute) {
					$attributes[$attribute->getPlainId()] = $attribute->toArray();
				}

				$controls = [];

				$findDeviceControlsQuery = new Queries\FindDeviceControls();
				$findDeviceControlsQuery->forDevice($device);

				foreach ($this->devicesControlsRepository->findAllBy($findDeviceControlsQuery) as $control) {
					$controls[$control->getPlainId()] = $control->toArray();
				}

				$devices[$device->getPlainId()] = array_merge($device->toArray(), [
					Devices\Constants::DATA_STORAGE_PROPERTIES_KEY => $properties,
					Devices\Constants::DATA_STORAGE_ATTRIBUTES_KEY => $attributes,
					Devices\Constants::DATA_STORAGE_CONTROLS_KEY => $controls,
					Devices\Constants::DATA_STORAGE_CHANNELS_KEY => $channels,
				]);
			}

			$properties = [];

			$findConnectorPropertiesQuery = new Queries\FindConnectorProperties();
			$findConnectorPropertiesQuery->forConnector($connector);

			foreach ($this->connectorsPropertiesRepository->findAllBy($findConnectorPropertiesQuery) as $property) {
				$properties[$property->getPlainId()] = $property->toArray();
			}

			$controls = [];

			$findConnectorControlsQuery = new Queries\FindConnectorControls();
			$findConnectorControlsQuery->forConnector($connector);

			foreach ($this->connectorsControlsRepository->findAllBy($findConnectorControlsQuery) as $control) {
				$controls[$control->getPlainId()] = $control->toArray();
			}

			$data[$connector->getPlainId()] = array_merge($connector->toArray(), [
				Devices\Constants::DATA_STORAGE_PROPERTIES_KEY => $properties,
				Devices\Constants::DATA_STORAGE_CONTROLS_KEY => $controls,
				Devices\Constants::DATA_STORAGE_DEVICES_KEY => $devices,
			]);
		}

		$this->filesystem->write(Devices\Constants::CONFIGURATION_FILE_FILENAME, Utils\Json::encode($data));

		$this->reader->read();

		$this->dispatcher?->dispatch(new Events\DataStorageWritten());
	}

}
