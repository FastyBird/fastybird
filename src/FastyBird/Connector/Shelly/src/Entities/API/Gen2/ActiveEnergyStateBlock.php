<?php declare(strict_types = 1);

/**
 * ActiveEnergyStateBlock.php
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

/**
 * Generation 2 device switch or cover component temperature state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActiveEnergyStateBlock implements Entities\API\Entity
{

	/**
	 * @param array<float> $byMinute
	 */
	public function __construct(
		#[ObjectMapper\Rules\FloatValue()]
		private readonly float $total,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		#[ObjectMapper\Modifiers\FieldName('by_minute')]
		private readonly array $byMinute,
		#[ObjectMapper\Rules\IntValue()]
		#[ObjectMapper\Modifiers\FieldName('minute_ts')]
		private readonly int $minuteTs,
	)
	{
	}

	public function getTotal(): float
	{
		return $this->total;
	}

	/**
	 * @return array<float>
	 */
	public function getByMinute(): array
	{
		return $this->byMinute;
	}

	public function getMinuteTs(): int
	{
		return $this->minuteTs;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'total' => $this->getTotal(),
			'by_minute' => $this->getByMinute(),
			'minute_ts' => $this->getMinuteTs(),
		];
	}

}
