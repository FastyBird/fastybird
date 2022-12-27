<?php declare(strict_types = 1);

/**
 * ActiveEnergyStatusBlock.php
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
final class ActiveEnergyStatusBlock implements Entity
{

	/**
	 * @param array<float> $byMinute
	 */
	public function __construct(
		private readonly float $total,
		private readonly array $byMinute,
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
