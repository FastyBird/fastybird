<?php declare(strict_types = 1);

/**
 * DeviceStatus.php
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
use Nette;
use function array_map;

/**
 * Generation 2 device status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<int, DeviceSwitchStatus> $switches
	 * @param array<int, DeviceCoverStatus> $covers
	 * @param array<int, DeviceInputStatus> $inputs
	 * @param array<int, DeviceLightStatus> $lights
	 * @param array<int, DeviceTemperatureStatus> $temperature
	 * @param array<int, DeviceHumidityStatus> $humidity
	 */
	public function __construct(
		private readonly array $switches = [],
		private readonly array $covers = [],
		private readonly array $inputs = [],
		private readonly array $lights = [],
		private readonly array $temperature = [],
		private readonly array $humidity = [],
		private readonly EthernetStatus|null $ethernet = null,
		private readonly WifiStatus|null $wifi = null,
	)
	{
	}

	/**
	 * @return array<DeviceSwitchStatus>
	 */
	public function getSwitches(): array
	{
		return $this->switches;
	}

	public function findSwitch(int $id): DeviceSwitchStatus|null
	{
		foreach ($this->switches as $switch) {
			if ($switch->getId() === $id) {
				return $switch;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceCoverStatus>
	 */
	public function getCovers(): array
	{
		return $this->covers;
	}

	public function findCover(int $id): DeviceCoverStatus|null
	{
		foreach ($this->covers as $cover) {
			if ($cover->getId() === $id) {
				return $cover;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceInputStatus>
	 */
	public function getInputs(): array
	{
		return $this->inputs;
	}

	public function findInput(int $id): DeviceInputStatus|null
	{
		foreach ($this->inputs as $input) {
			if ($input->getId() === $id) {
				return $input;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceLightStatus>
	 */
	public function getLights(): array
	{
		return $this->lights;
	}

	public function findLight(int $id): DeviceLightStatus|null
	{
		foreach ($this->lights as $light) {
			if ($light->getId() === $id) {
				return $light;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceTemperatureStatus>
	 */
	public function getTemperature(): array
	{
		return $this->temperature;
	}

	public function findTemperature(int $id): DeviceTemperatureStatus|null
	{
		foreach ($this->temperature as $temperature) {
			if ($temperature->getId() === $id) {
				return $temperature;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceHumidityStatus>
	 */
	public function getHumidity(): array
	{
		return $this->humidity;
	}

	public function findHumidity(int $id): DeviceHumidityStatus|null
	{
		foreach ($this->humidity as $humidity) {
			if ($humidity->getId() === $id) {
				return $humidity;
			}
		}

		return null;
	}

	public function getEthernet(): EthernetStatus|null
	{
		return $this->ethernet;
	}

	public function getWifi(): WifiStatus|null
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
				static fn (DeviceSwitchStatus $status): array => $status->toArray(),
				$this->getSwitches(),
			),
			'covers' => array_map(
				static fn (DeviceCoverStatus $status): array => $status->toArray(),
				$this->getCovers(),
			),
			'inputs' => array_map(
				static fn (DeviceInputStatus $status): array => $status->toArray(),
				$this->getInputs(),
			),
			'lights' => array_map(
				static fn (DeviceLightStatus $status): array => $status->toArray(),
				$this->getLights(),
			),
			'temperature' => array_map(
				static fn (DeviceTemperatureStatus $status): array => $status->toArray(),
				$this->getTemperature(),
			),
			'humidity' => array_map(
				static fn (DeviceHumidityStatus $status): array => $status->toArray(),
				$this->getHumidity(),
			),
			'ethernet' => $this->getEthernet()?->toArray(),
			'wifi' => $this->getWifi()?->toArray(),
		];
	}

}
