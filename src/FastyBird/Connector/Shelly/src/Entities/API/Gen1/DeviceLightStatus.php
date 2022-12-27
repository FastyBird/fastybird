<?php declare(strict_types = 1);

/**
 * DeviceLightStatus.php
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
 * Generation 1 device light status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceLightStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $ison,
		private readonly string $source,
		private readonly bool $hasTimer,
		private readonly int $timerStarted,
		private readonly int $timerDuration,
		private readonly int $timerRemaining,
		private readonly string $mode,
		private readonly int $red,
		private readonly int $green,
		private readonly int $blue,
		private readonly int $white,
		private readonly int $gain,
		private readonly int $temp,
		private readonly int $brightness,
		private readonly int $effect,
		private readonly int $transition,
	)
	{
	}

	public function getState(): bool
	{
		return $this->ison;
	}

	public function getSource(): string
	{
		return $this->source;
	}

	public function hasTimer(): bool
	{
		return $this->hasTimer;
	}

	public function getTimerStarted(): int
	{
		return $this->timerStarted;
	}

	public function getTimerDuration(): int
	{
		return $this->timerDuration;
	}

	public function getTimerRemaining(): int
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
		return $this->temp;
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
