<?php declare(strict_types = 1);

/**
 * DeviceTemperatureState.php
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

/**
 * Generation 2 device temperature state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceTemperatureState implements Entities\API\Entity
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		private readonly int $id,
		private readonly float|string|null $temperatureCelsius,
		private readonly float|string|null $temperatureFahrenheit,
		private readonly array $errors = [],
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::TEMPERATURE);
	}

	public function getTemperatureCelsius(): float|string|null
	{
		return $this->temperatureCelsius;
	}

	public function getTemperatureFahrenheit(): float|string|null
	{
		return $this->temperatureFahrenheit;
	}

	/**
	 * @return array<string>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->getValue(),
			'temperature_celsius' => $this->getTemperatureCelsius(),
			'temperature_fahrenheit' => $this->getTemperatureFahrenheit(),
			'errors' => $this->getErrors(),
		];
	}

}
