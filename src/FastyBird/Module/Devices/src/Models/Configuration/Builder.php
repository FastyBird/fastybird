<?php declare(strict_types = 1);

/**
 * Builder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           13.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration;

use Contributte\Cache;
use Evenement;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Flow\JSONPath;
use Nette\Caching;
use Orisai\DataSources;
use Throwable;
use function assert;
use function is_string;

/**
 * Configuration builder
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Builder implements Evenement\EventEmitterInterface
{

	use Evenement\EventEmitterTrait;

	private JSONPath\JSONPath|null $configuration = null;

	private Caching\Cache $cache;

	public function __construct(
		private readonly Models\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\Entities\Connectors\Properties\PropertiesRepository $connectorsPropertiesRepository,
		private readonly Models\Entities\Connectors\Controls\ControlsRepository $connectorsControlsRepository,
		private readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly Models\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly Models\Entities\Devices\Controls\ControlsRepository $devicesControlsRepository,
		private readonly Models\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly Models\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly Models\Entities\Channels\Controls\ControlsRepository $channelsControlsRepository,
		private readonly DataSources\DefaultDataSource $dataSource,
		private readonly Cache\CacheFactory $cacheFactory,
	)
	{
		$this->cache = $this->cacheFactory->create(
			MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES . '_configuration',
		);
	}

	public function clean(): void
	{
		$this->cache->remove(Devices\Constants::CONFIGURATION_KEY);
		$this->configuration = null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function load(bool $force = false): JSONPath\JSONPath
	{
		if ($this->configuration === null || $force) {
			try {
				if ($force) {
					$this->cache->remove(Devices\Constants::CONFIGURATION_KEY);
				}

				$data = $this->cache->load(
					Devices\Constants::CONFIGURATION_KEY,
					fn (): string => $this->build(),
				);
				assert(is_string($data));

				$decoded = $this->dataSource->decode($data, 'json');
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState('Module configuration could not be read', $ex->getCode(), $ex);
			}

			$this->configuration = new JSONPath\JSONPath($decoded);
		}

		return $this->configuration;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function build(): string
	{
		$data = [
			Devices\Constants::DATA_STORAGE_CONNECTORS_KEY => [],
			Devices\Constants::DATA_STORAGE_DEVICES_KEY => [],
			Devices\Constants::DATA_STORAGE_CHANNELS_KEY => [],
			Devices\Constants::DATA_STORAGE_PROPERTIES_KEY => [],
			Devices\Constants::DATA_STORAGE_CONTROLS_KEY => [],
		];

		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		foreach ($this->connectorsRepository->findAllBy($findConnectorsQuery) as $connector) {
			$data[Devices\Constants::DATA_STORAGE_CONNECTORS_KEY][] = $connector->toArray();
		}

		$findConnectorsPropertiesQuery = new Queries\Entities\FindConnectorProperties();

		foreach ($this->connectorsPropertiesRepository->findAllBy($findConnectorsPropertiesQuery) as $property) {
			$data[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY][] = $property->toArray();
		}

		$findConnectorsControlsQuery = new Queries\Entities\FindConnectorControls();

		foreach ($this->connectorsControlsRepository->findAllBy($findConnectorsControlsQuery) as $control) {
			$data[Devices\Constants::DATA_STORAGE_CONTROLS_KEY][] = $control->toArray();
		}

		$findDevicesQuery = new Queries\Entities\FindDevices();

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery) as $device) {
			$data[Devices\Constants::DATA_STORAGE_DEVICES_KEY][] = $device->toArray();
		}

		$findDevicesPropertiesQuery = new Queries\Entities\FindDeviceProperties();

		foreach ($this->devicesPropertiesRepository->findAllBy($findDevicesPropertiesQuery) as $property) {
			$data[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY][] = $property->toArray();
		}

		$findDevicesControlsQuery = new Queries\Entities\FindDeviceControls();

		foreach ($this->devicesControlsRepository->findAllBy($findDevicesControlsQuery) as $control) {
			$data[Devices\Constants::DATA_STORAGE_CONTROLS_KEY][] = $control->toArray();
		}

		$findChannelsQuery = new Queries\Entities\FindChannels();

		foreach ($this->channelsRepository->findAllBy($findChannelsQuery) as $channel) {
			$data[Devices\Constants::DATA_STORAGE_CHANNELS_KEY][] = $channel->toArray();
		}

		$findChannelsPropertiesQuery = new Queries\Entities\FindChannelProperties();

		foreach ($this->channelsPropertiesRepository->findAllBy($findChannelsPropertiesQuery) as $property) {
			$data[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY][] = $property->toArray();
		}

		$findChannelsControlsQuery = new Queries\Entities\FindChannelControls();

		foreach ($this->channelsControlsRepository->findAllBy($findChannelsControlsQuery) as $control) {
			$data[Devices\Constants::DATA_STORAGE_CONTROLS_KEY][] = $control->toArray();
		}

		$this->configuration = null;

		$this->emit('build');

		try {
			return $this->dataSource->encode($data, 'json');
		} catch (DataSources\Exception\NotSupportedType | DataSources\Exception\EncodingFailure $ex) {
			throw new Exceptions\InvalidState(
				'Module configuration structure could not be create',
				$ex->getCode(),
				$ex,
			);
		}
	}

}
