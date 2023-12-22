<?php declare(strict_types = 1);

/**
 * BatteryStateBlock.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           21.12.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use Orisai\ObjectMapper;

/**
 * Generation 2 device battery state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class BatteryStateBlock implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		)]
		#[ObjectMapper\Modifiers\FieldName('V')]
		private readonly float|null $voltage,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		)]
		private readonly int|null $percent,
	)
	{
	}

	public function getVoltage(): float|null
	{
		return $this->voltage;
	}

	public function getPercent(): int|null
	{
		return $this->percent;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'voltage' => $this->getVoltage(),
			'percent' => $this->getPercent(),
		];
	}

	/**
	 * @return array<string, float|int|null>
	 */
	public function toState(): array
	{
		return [
			'battery_voltage' => $this->getVoltage(),
			'battery_percent' => $this->getPercent(),
		];
	}

}
