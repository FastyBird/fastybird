<?php declare(strict_types = 1);

/**
 * ActiveEnergyStateBlock.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           27.12.22
 */

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;

/**
 * Generation 2 device switch or cover component temperature state message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActiveEnergyStateBlock implements API\Messages\Message
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

	/**
	 * @return array<string, float>
	 */
	public function toState(): array
	{
		return [
			'active_energy_total' => $this->getTotal(),
		];
	}

}
