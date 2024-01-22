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
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
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
final class Builder
{

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
			MetadataTypes\ModuleSource::DEVICES . '_configuration',
		);
	}

	public function clean(): void
	{
		$this->cache->clean([
			Caching\Cache::All => true,
		]);

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
	 * @throws ApplicationExceptions\InvalidState
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

		foreach ($this->connectorsRepository->findAll() as $connector) {
			$data[Devices\Constants::DATA_STORAGE_CONNECTORS_KEY][] = $connector->toArray();
		}

		foreach ($this->connectorsPropertiesRepository->findAll() as $property) {
			$data[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY][] = $property->toArray();
		}

		foreach ($this->connectorsControlsRepository->findAll() as $control) {
			$data[Devices\Constants::DATA_STORAGE_CONTROLS_KEY][] = $control->toArray();
		}

		foreach ($this->devicesRepository->findAll() as $device) {
			$data[Devices\Constants::DATA_STORAGE_DEVICES_KEY][] = $device->toArray();
		}

		foreach ($this->devicesPropertiesRepository->findAll() as $property) {
			$data[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY][] = $property->toArray();
		}

		foreach ($this->devicesControlsRepository->findAll() as $control) {
			$data[Devices\Constants::DATA_STORAGE_CONTROLS_KEY][] = $control->toArray();
		}

		foreach ($this->channelsRepository->findAll() as $channel) {
			$data[Devices\Constants::DATA_STORAGE_CHANNELS_KEY][] = $channel->toArray();
		}

		foreach ($this->channelsPropertiesRepository->findAll() as $property) {
			$data[Devices\Constants::DATA_STORAGE_PROPERTIES_KEY][] = $property->toArray();
		}

		foreach ($this->channelsControlsRepository->findAll() as $control) {
			$data[Devices\Constants::DATA_STORAGE_CONTROLS_KEY][] = $control->toArray();
		}

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
