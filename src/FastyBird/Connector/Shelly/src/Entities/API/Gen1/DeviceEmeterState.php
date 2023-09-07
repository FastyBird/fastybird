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
use Orisai\ObjectMapper;

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
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('active_power')]
		private readonly float $activePower,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('power_factor')]
		private readonly float $powerFactor,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('reactive_power')]
		private readonly float $reactivePower,
		#[ObjectMapper\Rules\FloatValue()]
		private readonly float $current,
		#[ObjectMapper\Rules\FloatValue()]
		private readonly float $voltage,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private readonly bool $valid,
		#[ObjectMapper\Rules\FloatValue()]
		private readonly float $total,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('total_returned')]
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
