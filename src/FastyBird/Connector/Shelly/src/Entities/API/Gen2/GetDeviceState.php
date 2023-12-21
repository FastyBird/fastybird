<?php declare(strict_types = 1);

/**
 * GetDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Generation 2 device state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GetDeviceState implements Entities\API\Entity
{

	/**
	 * @param array<int, DeviceSwitchState> $switches
	 * @param array<int, DeviceCoverState> $covers
	 * @param array<int, DeviceInputState> $inputs
	 * @param array<int, DeviceLightState> $lights
	 * @param array<int, DeviceTemperatureState> $temperature
	 * @param array<int, DeviceHumidityState> $humidity
	 * @param array<int, DeviceDevicePowerState> $devicePower
	 * @param array<int, DeviceScriptState> $scripts
	 * @param array<int, DeviceSmokeState> $smoke
	 * @param array<int, DeviceVoltmeterState> $voltmeters
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceSwitchState::class),
		)]
		private readonly array $switches = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceCoverState::class),
		)]
		private readonly array $covers = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceInputState::class),
		)]
		private readonly array $inputs = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceLightState::class),
		)]
		private readonly array $lights = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceTemperatureState::class),
		)]
		private readonly array $temperature = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceHumidityState::class),
		)]
		private readonly array $humidity = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceDevicePowerState::class),
		)]
		private readonly array $devicePower = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceScriptState::class),
		)]
		private readonly array $scripts = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceSmokeState::class),
		)]
		private readonly array $smoke = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceVoltmeterState::class),
		)]
		private readonly array $voltmeters = [],
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: EthernetState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly EthernetState|null $ethernet = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: WifiState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly WifiState|null $wifi = null,
	)
	{
	}

	/**
	 * @return array<DeviceSwitchState>
	 */
	public function getSwitches(): array
	{
		return $this->switches;
	}

	public function findSwitch(int $id): DeviceSwitchState|null
	{
		foreach ($this->switches as $switch) {
			if ($switch->getId() === $id) {
				return $switch;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceCoverState>
	 */
	public function getCovers(): array
	{
		return $this->covers;
	}

	public function findCover(int $id): DeviceCoverState|null
	{
		foreach ($this->covers as $cover) {
			if ($cover->getId() === $id) {
				return $cover;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceInputState>
	 */
	public function getInputs(): array
	{
		return $this->inputs;
	}

	public function findInput(int $id): DeviceInputState|null
	{
		foreach ($this->inputs as $input) {
			if ($input->getId() === $id) {
				return $input;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceLightState>
	 */
	public function getLights(): array
	{
		return $this->lights;
	}

	public function findLight(int $id): DeviceLightState|null
	{
		foreach ($this->lights as $light) {
			if ($light->getId() === $id) {
				return $light;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceTemperatureState>
	 */
	public function getTemperature(): array
	{
		return $this->temperature;
	}

	public function findTemperature(int $id): DeviceTemperatureState|null
	{
		foreach ($this->temperature as $temperature) {
			if ($temperature->getId() === $id) {
				return $temperature;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceHumidityState>
	 */
	public function getHumidity(): array
	{
		return $this->humidity;
	}

	public function findHumidity(int $id): DeviceHumidityState|null
	{
		foreach ($this->humidity as $humidity) {
			if ($humidity->getId() === $id) {
				return $humidity;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceDevicePowerState>
	 */
	public function getDevicePower(): array
	{
		return $this->devicePower;
	}

	public function findDevicePower(int $id): DeviceDevicePowerState|null
	{
		foreach ($this->devicePower as $devicePower) {
			if ($devicePower->getId() === $id) {
				return $devicePower;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceScriptState>
	 */
	public function getScripts(): array
	{
		return $this->scripts;
	}

	public function findScript(int $id): DeviceScriptState|null
	{
		foreach ($this->scripts as $scripts) {
			if ($scripts->getId() === $id) {
				return $scripts;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceSmokeState>
	 */
	public function getSmoke(): array
	{
		return $this->smoke;
	}

	public function findSmoke(int $id): DeviceSmokeState|null
	{
		foreach ($this->smoke as $smoke) {
			if ($smoke->getId() === $id) {
				return $smoke;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceVoltmeterState>
	 */
	public function getVoltmeters(): array
	{
		return $this->voltmeters;
	}

	public function findVoltmeter(int $id): DeviceVoltmeterState|null
	{
		foreach ($this->voltmeters as $voltmeter) {
			if ($voltmeter->getId() === $id) {
				return $voltmeter;
			}
		}

		return null;
	}

	public function findComponent(Types\ComponentType $type, int $id): DeviceState|null
	{
		switch ($type->getValue()) {
			case Types\ComponentType::SWITCH:
				return $this->findSwitch($id);
			case Types\ComponentType::COVER:
				return $this->findCover($id);
			case Types\ComponentType::LIGHT:
				return $this->findLight($id);
			case Types\ComponentType::INPUT:
				return $this->findInput($id);
			case Types\ComponentType::TEMPERATURE:
				return $this->findTemperature($id);
			case Types\ComponentType::HUMIDITY:
				return $this->findHumidity($id);
			case Types\ComponentType::VOLTMETER:
				return $this->findVoltmeter($id);
			case Types\ComponentType::SCRIPT:
				return $this->findScript($id);
			case Types\ComponentType::DEVICE_POWER:
				return $this->findDevicePower($id);
			case Types\ComponentType::SMOKE:
				return $this->findSmoke($id);
		}

		return null;
	}

	public function getEthernet(): EthernetState|null
	{
		return $this->ethernet;
	}

	public function getWifi(): WifiState|null
	{
		return $this->wifi;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'switches' => array_map(
				static fn (DeviceSwitchState $state): array => $state->toArray(),
				$this->getSwitches(),
			),
			'covers' => array_map(
				static fn (DeviceCoverState $state): array => $state->toArray(),
				$this->getCovers(),
			),
			'inputs' => array_map(
				static fn (DeviceInputState $state): array => $state->toArray(),
				$this->getInputs(),
			),
			'lights' => array_map(
				static fn (DeviceLightState $state): array => $state->toArray(),
				$this->getLights(),
			),
			'temperature' => array_map(
				static fn (DeviceTemperatureState $state): array => $state->toArray(),
				$this->getTemperature(),
			),
			'humidity' => array_map(
				static fn (DeviceHumidityState $state): array => $state->toArray(),
				$this->getHumidity(),
			),
			'device_power' => array_map(
				static fn (DeviceDevicePowerState $state): array => $state->toArray(),
				$this->getDevicePower(),
			),
			'scripts' => array_map(
				static fn (DeviceScriptState $state): array => $state->toArray(),
				$this->getScripts(),
			),
			'smoke' => array_map(
				static fn (DeviceSmokeState $state): array => $state->toArray(),
				$this->getSmoke(),
			),
			'voltmeters' => array_map(
				static fn (DeviceVoltmeterState $state): array => $state->toArray(),
				$this->getVoltmeters(),
			),
			'ethernet' => $this->getEthernet()?->toArray(),
			'wifi' => $this->getWifi()?->toArray(),
		];
	}

}
