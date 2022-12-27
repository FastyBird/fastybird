<?php declare(strict_types = 1);

/**
 * CoverMotorConfigurationBlock.php
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
 * Generation 2 device cover component motor configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CoverMotorConfigurationBlock implements Entity
{

	public function __construct(
		private readonly float $idlePowerThr,
		private readonly float $idleConfirmPeriod,
	)
	{
	}

	public function getIdlePowerThreshold(): float
	{
		return $this->idlePowerThr;
	}

	public function getIdleConfirmPeriod(): float
	{
		return $this->idleConfirmPeriod;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'idle_power_threshold' => $this->getIdlePowerThreshold(),
			'idle_confirm_period' => $this->getIdleConfirmPeriod(),
		];
	}

}
