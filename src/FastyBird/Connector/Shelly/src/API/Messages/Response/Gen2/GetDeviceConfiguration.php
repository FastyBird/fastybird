<?php declare(strict_types = 1);

/**
 * GetDeviceConfiguration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Generation 2 device configuration message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GetDeviceConfiguration implements API\Messages\Message
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
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::SWITCH)]
		private readonly array $switches = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceCoverConfiguration::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::COVER)]
		private readonly array $covers = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceInputConfiguration::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::INPUT)]
		private readonly array $inputs = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceLightConfiguration::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::LIGHT)]
		private readonly array $lights = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceTemperatureConfiguration::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::TEMPERATURE)]
		private readonly array $temperature = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceHumidityConfiguration::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::HUMIDITY)]
		private readonly array $humidity = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceDevicePowerConfiguration::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::DEVICE_POWER)]
		private readonly array $devicePower = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceScriptConfiguration::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::SCRIPT)]
		private readonly array $scripts = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceSmokeConfiguration::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::SMOKE)]
		private readonly array $smoke = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceVoltmeterConfiguration::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::VOLTMETER)]
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
			Types\ComponentType::SWITCH => array_map(
				static fn (DeviceSwitchConfiguration $configuration): array => $configuration->toArray(),
				$this->getSwitches(),
			),
			Types\ComponentType::COVER => array_map(
				static fn (DeviceCoverConfiguration $configuration): array => $configuration->toArray(),
				$this->getCovers(),
			),
			Types\ComponentType::INPUT => array_map(
				static fn (DeviceInputConfiguration $configuration): array => $configuration->toArray(),
				$this->getInputs(),
			),
			Types\ComponentType::LIGHT => array_map(
				static fn (DeviceLightConfiguration $configuration): array => $configuration->toArray(),
				$this->getLights(),
			),
			Types\ComponentType::TEMPERATURE => array_map(
				static fn (DeviceTemperatureConfiguration $configuration): array => $configuration->toArray(),
				$this->getTemperature(),
			),
			Types\ComponentType::HUMIDITY => array_map(
				static fn (DeviceHumidityConfiguration $configuration): array => $configuration->toArray(),
				$this->getHumidity(),
			),
			Types\ComponentType::DEVICE_POWER => array_map(
				static fn (DeviceDevicePowerConfiguration $configuration): array => $configuration->toArray(),
				$this->getDevicePower(),
			),
			Types\ComponentType::SCRIPT => array_map(
				static fn (DeviceScriptConfiguration $configuration): array => $configuration->toArray(),
				$this->getScripts(),
			),
			Types\ComponentType::SMOKE => array_map(
				static fn (DeviceSmokeConfiguration $configuration): array => $configuration->toArray(),
				$this->getSmoke(),
			),
			Types\ComponentType::VOLTMETER => array_map(
				static fn (DeviceVoltmeterConfiguration $configuration): array => $configuration->toArray(),
				$this->getVoltmeters(),
			),
		];
	}

}
