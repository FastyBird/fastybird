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
	 */
	public function __construct(
		private readonly array $switches = [],
		private readonly array $covers = [],
		private readonly array $inputs = [],
		private readonly array $lights = [],
		private readonly DeviceTemperatureStatus|null $temperature = null,
		private readonly DeviceHumidityStatus|null $humidity = null,
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

	/**
	 * @return array<DeviceCoverStatus>
	 */
	public function getCovers(): array
	{
		return $this->covers;
	}

	/**
	 * @return array<DeviceInputStatus>
	 */
	public function getInputs(): array
	{
		return $this->inputs;
	}

	/**
	 * @return array<DeviceLightStatus>
	 */
	public function getLights(): array
	{
		return $this->lights;
	}

	public function getTemperature(): DeviceTemperatureStatus|null
	{
		return $this->temperature;
	}

	public function getHumidity(): DeviceHumidityStatus|null
	{
		return $this->humidity;
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
			'temperature' => $this->getTemperature()?->toArray(),
			'humidity' => $this->getHumidity()?->toArray(),
			'ethernet' => $this->getEthernet()?->toArray(),
			'wifi' => $this->getWifi()?->toArray(),
		];
	}

}
