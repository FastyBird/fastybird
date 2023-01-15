<?php declare(strict_types = 1);

/**
 * DeviceRollerStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           22.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;
use Nette;

/**
 * Generation 1 device roller status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceRollerStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $state,
		private readonly int $power,
		private readonly bool $isValid,
		private readonly bool $safetySwitch,
		private readonly bool $overtemperature,
		private readonly string $stopReason,
		private readonly string $lastDirection,
		private readonly int $currentPos,
		private readonly bool $calibrating,
		private readonly bool $positioning,
	)
	{
	}

	public function getState(): string
	{
		return $this->state;
	}

	public function getPower(): int
	{
		return $this->power;
	}

	public function isValid(): bool
	{
		return $this->isValid;
	}

	public function hasSafetySwitch(): bool
	{
		return $this->safetySwitch;
	}

	public function hasOvertemperature(): bool
	{
		return $this->overtemperature;
	}

	public function getStopReason(): string
	{
		return $this->stopReason;
	}

	public function getLastDirection(): string
	{
		return $this->lastDirection;
	}

	public function getCurrentPosition(): int
	{
		return $this->currentPos;
	}

	public function isCalibrating(): bool
	{
		return $this->calibrating;
	}

	public function isPositioning(): bool
	{
		return $this->positioning;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'state' => $this->getState(),
			'power' => $this->getPower(),
			'valid' => $this->isValid(),
			'safety_switch' => $this->hasSafetySwitch(),
			'overtemperature' => $this->hasOvertemperature(),
			'stop_reason' => $this->getStopReason(),
			'last_direction' => $this->getLastDirection(),
			'current_pos' => $this->getCurrentPosition(),
			'calibrating' => $this->isCalibrating(),
			'positioning' => $this->isPositioning(),
		];
	}

}
