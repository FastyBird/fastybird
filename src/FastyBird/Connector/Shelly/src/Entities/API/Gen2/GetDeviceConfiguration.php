<?php declare(strict_types = 1);

/**
 * GetDeviceConfiguration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Generation 2 device configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GetDeviceConfiguration implements Entities\API\Entity
{

	/**
	 * @param array<int, DeviceSwitchConfiguration> $switches
	 * @param array<int, DeviceCoverConfiguration> $covers
	 * @param array<int, DeviceInputConfiguration> $inputs
	 * @param array<int, DeviceLightConfiguration> $lights
	 * @param array<int, DeviceTemperatureConfiguration> $temperature
	 * @param array<int, DeviceHumidityConfiguration> $humidity
	 * @param array<int, DeviceDevicePowerConfiguration> $devicePower
	 * @param array<int, DeviceScriptConfiguration> $scripts
	 * @param array<int, DeviceSmokeConfiguration> $smoke
	 * @param array<int, DeviceVoltmeterConfiguration> $voltmeters
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceSwitchConfiguration::class),
		)]
		private readonly array $switches = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceCoverConfiguration::class),
		)]
		private readonly array $covers = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceInputConfiguration::class),
		)]
		private readonly array $inputs = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceLightConfiguration::class),
		)]
		private readonly array $lights = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceTemperatureConfiguration::class),
		)]
		private readonly array $temperature = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceHumidityConfiguration::class),
		)]
		private readonly array $humidity = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceDevicePowerConfiguration::class),
		)]
		private readonly array $devicePower = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceScriptConfiguration::class),
		)]
		private readonly array $scripts = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceSmokeConfiguration::class),
		)]
		private readonly array $smoke = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceVoltmeterConfiguration::class),
		)]
		private readonly array $voltmeters = [],
	)
	{
	}

	/**
	 * @return array<DeviceSwitchConfiguration>
	 */
	public function getSwitches(): array
	{
		return $this->switches;
	}

	/**
	 * @return array<DeviceCoverConfiguration>
	 */
	public function getCovers(): array
	{
		return $this->covers;
	}

	/**
	 * @return array<DeviceInputConfiguration>
	 */
	public function getInputs(): array
	{
		return $this->inputs;
	}

	/**
	 * @return array<DeviceLightConfiguration>
	 */
	public function getLights(): array
	{
		return $this->lights;
	}

	/**
	 * @return array<DeviceTemperatureConfiguration>
	 */
	public function getTemperature(): array
	{
		return $this->temperature;
	}

	/**
	 * @return array<DeviceHumidityConfiguration>
	 */
	public function getHumidity(): array
	{
		return $this->humidity;
	}

	/**
	 * @return array<DeviceDevicePowerConfiguration>
	 */
	public function getDevicePower(): array
	{
		return $this->devicePower;
	}

	/**
	 * @return array<DeviceScriptConfiguration>
	 */
	public function getScripts(): array
	{
		return $this->scripts;
	}

	/**
	 * @return array<DeviceSmokeConfiguration>
	 */
	public function getSmoke(): array
	{
		return $this->smoke;
	}

	/**
	 * @return array<DeviceVoltmeterConfiguration>
	 */
	public function getVoltmeters(): array
	{
		return $this->voltmeters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'switches' => array_map(
				static fn (DeviceSwitchConfiguration $configuration): array => $configuration->toArray(),
				$this->getSwitches(),
			),
			'covers' => array_map(
				static fn (DeviceCoverConfiguration $configuration): array => $configuration->toArray(),
				$this->getCovers(),
			),
			'inputs' => array_map(
				static fn (DeviceInputConfiguration $configuration): array => $configuration->toArray(),
				$this->getInputs(),
			),
			'lights' => array_map(
				static fn (DeviceLightConfiguration $configuration): array => $configuration->toArray(),
				$this->getLights(),
			),
			'temperature' => array_map(
				static fn (DeviceTemperatureConfiguration $configuration): array => $configuration->toArray(),
				$this->getTemperature(),
			),
			'humidity' => array_map(
				static fn (DeviceHumidityConfiguration $configuration): array => $configuration->toArray(),
				$this->getHumidity(),
			),
			'device_power' => array_map(
				static fn (DeviceDevicePowerConfiguration $configuration): array => $configuration->toArray(),
				$this->getDevicePower(),
			),
			'scripts' => array_map(
				static fn (DeviceScriptConfiguration $configuration): array => $configuration->toArray(),
				$this->getScripts(),
			),
			'smoke' => array_map(
				static fn (DeviceSmokeConfiguration $configuration): array => $configuration->toArray(),
				$this->getSmoke(),
			),
			'voltmeters' => array_map(
				static fn (DeviceVoltmeterConfiguration $configuration): array => $configuration->toArray(),
				$this->getVoltmeters(),
			),
		];
	}

}
