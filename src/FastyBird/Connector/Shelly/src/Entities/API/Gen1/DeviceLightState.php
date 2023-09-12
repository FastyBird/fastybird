<?php declare(strict_types = 1);

/**
 * DeviceLightState.php
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
 * Generation 1 device light state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceLightState implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('ison')]
		private readonly bool $state,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $source,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('has_timer')]
		private readonly bool $hasTimer,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('timer_started')]
		private readonly float $timerStarted,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('timer_duration')]
		private readonly float $timerDuration,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('timer_remaining')]
		private readonly float $timerRemaining,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['color', 'white'])]
		private readonly string $mode,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		private readonly int $red,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		private readonly int $green,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		private readonly int $blue,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		private readonly int $white,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		private readonly int $gain,
		#[ObjectMapper\Rules\IntValue(min: 2_700, max: 6_500, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName('temp')]
		private readonly int $temperature,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		private readonly int $brightness,
		#[ObjectMapper\Rules\IntValue()]
		private readonly int $effect,
		#[ObjectMapper\Rules\IntValue()]
		private readonly int $transition,
	)
	{
	}

	public function getState(): bool
	{
		return $this->state;
	}

	public function getSource(): string
	{
		return $this->source;
	}

	public function hasTimer(): bool
	{
		return $this->hasTimer;
	}

	public function getTimerStarted(): float
	{
		return $this->timerStarted;
	}

	public function getTimerDuration(): float
	{
		return $this->timerDuration;
	}

	public function getTimerRemaining(): float
	{
		return $this->timerRemaining;
	}

	public function getMode(): string
	{
		return $this->mode;
	}

	public function getRed(): int
	{
		return $this->red;
	}

	public function getGreen(): int
	{
		return $this->green;
	}

	public function getBlue(): int
	{
		return $this->blue;
	}

	public function getWhite(): int
	{
		return $this->white;
	}

	public function getGain(): int
	{
		return $this->gain;
	}

	public function getTemperature(): int
	{
		return $this->temperature;
	}

	public function getBrightness(): int
	{
		return $this->brightness;
	}

	public function getEffect(): int
	{
		return $this->effect;
	}

	public function getTransition(): int
	{
		return $this->transition;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'state' => $this->getState(),
			'source' => $this->getSource(),
			'has_timer' => $this->hasTimer(),
			'timer_started' => $this->getTimerStarted(),
			'timer_duration' => $this->getTimerDuration(),
			'timer_remaining' => $this->getTimerRemaining(),
			'mode' => $this->getMode(),
			'red' => $this->getRed(),
			'green' => $this->getGreen(),
			'blue' => $this->getBlue(),
			'white' => $this->getWhite(),
			'gain' => $this->getGain(),
			'temperature' => $this->getTemperature(),
			'brightness' => $this->getBrightness(),
			'effect' => $this->getEffect(),
			'transition' => $this->getTransition(),
		];
	}

}
