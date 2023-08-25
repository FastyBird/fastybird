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
	 */
	public function __construct(
		private readonly array              $switches = [],
		private readonly array              $covers = [],
		private readonly array              $inputs = [],
		private readonly array              $lights = [],
		private readonly array              $temperature = [],
		private readonly array              $humidity = [],
		private readonly EthernetState|null $ethernet = null,
		private readonly WifiState|null     $wifi = null,
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
			'ethernet' => $this->getEthernet()?->toArray(),
			'wifi' => $this->getWifi()?->toArray(),
		];
	}

}
