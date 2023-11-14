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

use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Flow\JSONPath;
use Nette;
use Nette\Utils;
use Orisai\DataSources;
use Throwable;
use const DIRECTORY_SEPARATOR;

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

	private DataSources\DefaultDataSource $dataSource;

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
	)
	{
		$manager = new DataSources\DefaultFormatEncoderManager();
		$manager->addEncoder(new DataSources\JsonFormatEncoder());

		$this->dataSource = new DataSources\DefaultDataSource($manager);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function build(): void
	{
		$data = [
			'connectors' => [],
			'devices' => [],
			'channels' => [],
			'properties' => [],
			'controls' => [],
		];

		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		foreach ($this->connectorsRepository->findAllBy($findConnectorsQuery) as $connector) {
			$data['connectors'][] = $connector->toArray();
		}

		$findConnectorsPropertiesQuery = new Queries\Entities\FindConnectorProperties();

		foreach ($this->connectorsPropertiesRepository->findAllBy($findConnectorsPropertiesQuery) as $property) {
			$data['properties'][] = $property->toArray();
		}

		$findConnectorsControlsQuery = new Queries\Entities\FindConnectorControls();

		foreach ($this->connectorsControlsRepository->findAllBy($findConnectorsControlsQuery) as $control) {
			$data['controls'][] = $control->toArray();
		}

		$findDevicesQuery = new Queries\Entities\FindDevices();

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery) as $device) {
			$data['devices'][] = $device->toArray();
		}

		$findDevicesPropertiesQuery = new Queries\Entities\FindDeviceProperties();

		foreach ($this->devicesPropertiesRepository->findAllBy($findDevicesPropertiesQuery) as $property) {
			$data['properties'][] = $property->toArray();
		}

		$findDevicesControlsQuery = new Queries\Entities\FindDeviceControls();

		foreach ($this->devicesControlsRepository->findAllBy($findDevicesControlsQuery) as $control) {
			$data['controls'][] = $control->toArray();
		}

		$findChannelsQuery = new Queries\Entities\FindChannels();

		foreach ($this->channelsRepository->findAllBy($findChannelsQuery) as $channel) {
			$data['channels'][] = $channel->toArray();
		}

		$findChannelsPropertiesQuery = new Queries\Entities\FindChannelProperties();

		foreach ($this->channelsPropertiesRepository->findAllBy($findChannelsPropertiesQuery) as $property) {
			$data['properties'][] = $property->toArray();
		}

		$findChannelsControlsQuery = new Queries\Entities\FindChannelControls();

		foreach ($this->channelsControlsRepository->findAllBy($findChannelsControlsQuery) as $control) {
			$data['controls'][] = $control->toArray();
		}

		$this->encode($data);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function load(): JSONPath\JSONPath
	{
		return new JSONPath\JSONPath($this->decode());
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function encode(array $data): void
	{
		try {
			Utils\FileSystem::write(
				FB_TEMP_DIR . DIRECTORY_SEPARATOR . 'devices_module.json',
				$this->dataSource->encode($data, 'json'),
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Module configuration could not be written', $ex->getCode(), $ex);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function decode(): mixed
	{
		try {
			return $this->dataSource->decode(
				Utils\FileSystem::read(FB_TEMP_DIR . DIRECTORY_SEPARATOR . 'devices_module.json'),
				'json',
			);
		} catch (Nette\IOException) {
			$this->build();

			return $this->decode();
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('Module configuration could not be read', $ex->getCode(), $ex);
		}
	}

}