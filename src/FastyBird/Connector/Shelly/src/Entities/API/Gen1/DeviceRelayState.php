<?php declare(strict_types = 1);

/**
 * DeviceRelayState.php
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

/**
 * Generation 1 device relay state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceRelayState implements Entities\API\Entity
{

	public function __construct(
		private readonly bool $state,
		private readonly bool $hasTimer,
		private readonly int $timerStarted,
		private readonly int $timerDuration,
		private readonly int $timerRemaining,
		private readonly bool $overpower,
		private readonly bool $overtemperature,
		private readonly bool $valid,
		private readonly string|null $source,
	)
	{
	}

	public function getState(): bool
	{
		return $this->state;
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

	public function hasOverpower(): bool
	{
		return $this->overpower;
	}

	public function hasOvertemperature(): bool
	{
		return $this->overtemperature;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	public function getSource(): string|null
	{
		return $this->source;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'state' => $this->getState(),
			'has_timer' => $this->hasTimer(),
			'timer_started' => $this->getTimerStarted(),
			'timer_duration' => $this->getTimerDuration(),
			'timer_remaining' => $this->getTimerRemaining(),
			'overpower' => $this->hasOverpower(),
			'overtemperature' => $this->hasOvertemperature(),
			'valid' => $this->isValid(),
			'source' => $this->getSource(),
		];
	}

}
