<?php declare(strict_types = 1);

/**
 * TemperatureBlockStatus.php
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

use FastyBird\Connector\Shelly\Entities\API\Entity;

/**
 * Generation 2 device switch or cover component temperature status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TemperatureBlockStatus implements Entity
{

	public function __construct(
		private readonly float|null $tC,
		private readonly float|null $tF,
	)
	{
	}

	public function getTemperatureCelsius(): float|null
	{
		return $this->tC;
	}

	public function getTemperatureFahrenheit(): float|null
	{
		return $this->tF;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'temperature_celsius' => $this->getTemperatureCelsius(),
			'temperature_fahrenheit' => $this->getTemperatureFahrenheit(),
		];
	}

}
