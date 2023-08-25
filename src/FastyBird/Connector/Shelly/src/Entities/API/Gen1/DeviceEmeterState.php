<?php declare(strict_types = 1);

/**
 * DeviceEmeterState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           03.01.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;

/**
 * Generation 1 device energy meter state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceEmeterState implements Entities\API\Entity
{

	public function __construct(
		private readonly float $activePower,
		private readonly float $powerFactor,
		private readonly float $reactivePower,
		private readonly float $current,
		private readonly float $voltage,
		private readonly bool $valid,
		private readonly float $total,
		private readonly float $totalReturned,
	)
	{
	}

	public function getActivePower(): float
	{
		return $this->activePower;
	}

	public function getPowerFactor(): float
	{
		return $this->powerFactor;
	}

	public function getReactivePower(): float
	{
		return $this->reactivePower;
	}

	public function getCurrent(): float
	{
		return $this->current;
	}

	public function getVoltage(): float
	{
		return $this->voltage;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	public function getTotal(): float
	{
		return $this->total;
	}

	public function getTotalReturned(): float
	{
		return $this->totalReturned;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'active_power' => $this->getActivePower(),
			'power_factor' => $this->getPowerFactor(),
			'reactive_power' => $this->getReactivePower(),
			'current' => $this->getCurrent(),
			'voltage' => $this->getVoltage(),
			'valid' => $this->isValid(),
			'total' => $this->getTotal(),
			'total_returned' => $this->getTotalReturned(),
		];
	}

}
