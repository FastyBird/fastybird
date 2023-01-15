<?php declare(strict_types = 1);

/**
 * DeviceCoverConfiguration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           26.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Nette;

/**
 * Generation 2 device input configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceCoverConfiguration implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $id,
		private readonly string|null $name,
		private readonly string $mode,
		private readonly string $initialState,
		private readonly float $powerLimit,
		private readonly float $voltageLimit,
		private readonly float $currentLimit,
		private readonly float $maxtimeOpen,
		private readonly float $maxtimeClose,
		private readonly bool $swapInputs,
		private readonly bool $invertDirections,
		private readonly CoverMotorConfigurationBlock|null $motor,
		private readonly CoverObstructionDetectionConfigurationBlock|null $obstructionDetection,
		private readonly CoverSafetySwitchConfigurationBlock|null $safetySwitch,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::TYPE_COVER);
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getMode(): string
	{
		return $this->mode;
	}

	public function getInitialState(): string
	{
		return $this->initialState;
	}

	public function getPowerLimit(): float
	{
		return $this->powerLimit;
	}

	public function getVoltageLimit(): float
	{
		return $this->voltageLimit;
	}

	public function getCurrentLimit(): float
	{
		return $this->currentLimit;
	}

	public function getMaximumOpeningTime(): float
	{
		return $this->maxtimeOpen;
	}

	public function getMaximumClosingTime(): float
	{
		return $this->maxtimeClose;
	}

	public function hasSwappedInputs(): bool
	{
		return $this->swapInputs;
	}

	public function hasInvertedDirection(): bool
	{
		return $this->invertDirections;
	}

	public function getMotor(): CoverMotorConfigurationBlock|null
	{
		return $this->motor;
	}

	public function getObstructionDetection(): CoverObstructionDetectionConfigurationBlock|null
	{
		return $this->obstructionDetection;
	}

	public function getSafetySwitch(): CoverSafetySwitchConfigurationBlock|null
	{
		return $this->safetySwitch;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->getValue(),
			'name' => $this->getName(),
			'mode' => $this->getMode(),
			'initial_state' => $this->getInitialState(),
			'power_limit' => $this->getPowerLimit(),
			'voltage_limit' => $this->getVoltageLimit(),
			'current_limit' => $this->getCurrentLimit(),
			'motor' => $this->getMotor()?->toArray(),
			'maximum_opening_time' => $this->getMaximumOpeningTime(),
			'maximum_closing_time' => $this->getMaximumClosingTime(),
			'swapped_input' => $this->hasSwappedInputs(),
			'inverted_directions' => $this->hasInvertedDirection(),
			'obstruction_detection' => $this->getObstructionDetection()?->toArray(),
			'safety_switch' => $this->getSafetySwitch()?->toArray(),
		];
	}

}
