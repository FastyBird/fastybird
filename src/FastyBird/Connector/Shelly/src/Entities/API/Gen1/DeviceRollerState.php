<?php declare(strict_types = 1);

/**
 * DeviceRollerState.php
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
use Orisai\ObjectMapper;

/**
 * Generation 1 device roller state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceRollerState implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $state,
		#[ObjectMapper\Rules\IntValue()]
		private readonly int $power,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $valid,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('safety_switch')]
		private readonly bool $safetySwitch,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $overtemperature,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('stop_reason')]
		private readonly string $stopReason,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('last_direction')]
		private readonly string $lastDirection,
		#[ObjectMapper\Rules\IntValue()]
		#[ObjectMapper\Modifiers\FieldName('current_pos')]
		private readonly int $currentPos,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $calibrating,
		#[ObjectMapper\Rules\BoolValue()]
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
		return $this->valid;
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
