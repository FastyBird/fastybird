<?php declare(strict_types = 1);

/**
 * DeviceSwitchState.php
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

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Nette\Utils;

/**
 * Generation 2 device switch state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceSwitchState implements Entities\API\Entity
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		private readonly int $id,
		private readonly string|null $source,
		private readonly bool|string $output,
		private readonly int|null $timerStartedAt,
		private readonly int|null $timerDuration,
		private readonly float|string $activePower,
		private readonly float|string $voltage,
		private readonly float|string $current,
		private readonly float|string $powerFactor,
		private readonly ActiveEnergyStateBlock|string $activeEnergy,
		private readonly TemperatureBlockState|string $temperature,
		private readonly array $errors = [],
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::SWITCH);
	}

	public function getSource(): string|null
	{
		return $this->source;
	}

	public function getOutput(): bool|string
	{
		return $this->output;
	}

	/**
	 * @throws Exception
	 */
	public function getTimerStartedAt(): DateTimeInterface|null
	{
		if ($this->timerStartedAt !== null) {
			return Utils\DateTime::from($this->timerStartedAt);
		}

		return null;
	}

	public function getTimerDuration(): int|null
	{
		return $this->timerDuration;
	}

	public function getActivePower(): float|string
	{
		return $this->activePower;
	}

	public function getVoltage(): float|string
	{
		return $this->voltage;
	}

	public function getCurrent(): float|string
	{
		return $this->current;
	}

	public function getPowerFactor(): float|string
	{
		return $this->powerFactor;
	}

	public function getActiveEnergy(): ActiveEnergyStateBlock|string
	{
		return $this->activeEnergy;
	}

	public function getTemperature(): TemperatureBlockState|string
	{
		return $this->temperature;
	}

	/**
	 * @return array<string>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->getValue(),
			'source' => $this->getSource(),
			'output' => $this->getOutput(),
			'timer_started_at' => $this->getTimerStartedAt()?->format(DateTimeInterface::ATOM),
			'timer_duration' => $this->getTimerDuration(),
			'active_power' => $this->getActivePower(),
			'voltage' => $this->getVoltage(),
			'current' => $this->getCurrent(),
			'power_factor' => $this->getPowerFactor(),
			'active_energy' => $this->getActiveEnergy() instanceof ActiveEnergyStateBlock ? $this->getActiveEnergy()->toArray() : null,
			'temperature' => $this->getTemperature() instanceof TemperatureBlockState ? $this->getTemperature()->toArray() : null,
			'errors' => $this->getErrors(),
		];
	}

}
