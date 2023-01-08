<?php declare(strict_types = 1);

/**
 * DeviceEmeterStatus.php
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
use Nette;

/**
 * Generation 1 device energy meter status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceEmeterStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly float $power,
		private readonly float $pf,
		private readonly float $reactive,
		private readonly float $current,
		private readonly float $voltage,
		private readonly bool $isValid,
		private readonly float $total,
		private readonly float $totalReturned,
	)
	{
	}

	public function getActivePower(): float
	{
		return $this->power;
	}

	public function getPowerFactor(): float
	{
		return $this->pf;
	}

	public function getReactivePower(): float
	{
		return $this->reactive;
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
		return $this->isValid;
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
