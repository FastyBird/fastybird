<?php declare(strict_types = 1);

/**
 * DeviceSwitchStatus.php
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
use Nette;
use Nette\Utils;

/**
 * Generation 2 device switch status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceSwitchStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		private readonly int $id,
		private readonly string $source,
		private readonly bool $output,
		private readonly int|null $timerStartedAt,
		private readonly int|null $timerDuration,
		private readonly float|null $apower,
		private readonly float|null $voltage,
		private readonly float|null $current,
		private readonly float|null $pf,
		private readonly ActiveEnergyStatusBlock|null $aenergy,
		private readonly TemperatureBlockStatus|null $temperature,
		private readonly array $errors = [],
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getSource(): string
	{
		return $this->source;
	}

	public function getOutput(): bool
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

	public function getActivePower(): float|null
	{
		return $this->apower;
	}

	public function getVoltage(): float|null
	{
		return $this->voltage;
	}

	public function getCurrent(): float|null
	{
		return $this->current;
	}

	public function getPowerFactor(): float|null
	{
		return $this->pf;
	}

	public function getActiveEnergy(): ActiveEnergyStatusBlock|null
	{
		return $this->aenergy;
	}

	public function getTemperature(): TemperatureBlockStatus|null
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
			'source' => $this->getSource(),
			'output' => $this->getOutput(),
			'timer_started_at' => $this->getTimerStartedAt()?->format(DateTimeInterface::ATOM),
			'timer_duration' => $this->getTimerDuration(),
			'active_power' => $this->getActivePower(),
			'voltage' => $this->getVoltage(),
			'current' => $this->getCurrent(),
			'power_factor' => $this->getPowerFactor(),
			'active_energy' => $this->getActiveEnergy()?->toArray(),
			'temperature' => $this->getTemperature()?->toArray(),
			'errors' => $this->getErrors(),
		];
	}

}
