<?php declare(strict_types = 1);

/**
 * TemperatureBlockState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           27.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * Generation 2 device switch or cover component temperature state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TemperatureBlockState implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('tC')]
		private readonly float|null $temperatureCelsius,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('tF')]
		private readonly float|null $temperatureFahrenheit,
	)
	{
	}

	public function getTemperatureCelsius(): float|null
	{
		return $this->temperatureCelsius;
	}

	public function getTemperatureFahrenheit(): float|null
	{
		return $this->temperatureFahrenheit;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'celsius' => $this->getTemperatureCelsius(),
			'fahrenheit' => $this->getTemperatureFahrenheit(),
		];
	}

	/**
	 * @return array<string, float>
	 */
	public function toState(): array
	{
		return array_merge(
			$this->getTemperatureCelsius() !== null ? ['temperature_celsius' => $this->getTemperatureCelsius()] : [],
			$this->getTemperatureFahrenheit() !== null ? ['temperature_fahrenheit' => $this->getTemperatureFahrenheit()] : [],
		);
	}

}
