<?php declare(strict_types = 1);

/**
 * DeviceConfiguration.php
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
use function array_map;

/**
 * Generation 2 device configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceConfiguration implements Entities\API\Entity
{

	/**
	 * @param array<int, DeviceSwitchConfiguration> $switches
	 * @param array<int, DeviceCoverConfiguration> $covers
	 * @param array<int, DeviceInputConfiguration> $inputs
	 * @param array<int, DeviceLightConfiguration> $lights
	 */
	public function __construct(
		private readonly array $switches = [],
		private readonly array $covers = [],
		private readonly array $inputs = [],
		private readonly array $lights = [],
		private readonly DeviceTemperatureConfiguration|null $temperature = null,
		private readonly DeviceHumidityConfiguration|null $humidity = null,
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

	public function getTemperature(): DeviceTemperatureConfiguration|null
	{
		return $this->temperature;
	}

	public function getHumidity(): DeviceHumidityConfiguration|null
	{
		return $this->humidity;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'switches' => array_map(
				static fn (DeviceSwitchConfiguration $status): array => $status->toArray(),
				$this->getSwitches(),
			),
			'covers' => array_map(
				static fn (DeviceCoverConfiguration $status): array => $status->toArray(),
				$this->getCovers(),
			),
			'inputs' => array_map(
				static fn (DeviceInputConfiguration $status): array => $status->toArray(),
				$this->getInputs(),
			),
			'lights' => array_map(
				static fn (DeviceLightConfiguration $status): array => $status->toArray(),
				$this->getLights(),
			),
			'temperature' => $this->getTemperature()?->toArray(),
			'humidity' => $this->getHumidity()?->toArray(),
		];
	}

}
