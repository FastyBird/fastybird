<?php declare(strict_types = 1);

/**
 * DeviceCoverStatus.php
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
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use Nette;
use Nette\Utils;

/**
 * Generation 2 device cover status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceCoverStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		private readonly int $id,
		private readonly string $source,
		private readonly string $state,
		private readonly float|string $apower,
		private readonly float|string $voltage,
		private readonly float|string $current,
		private readonly float|string $pf,
		private readonly int|null $currentPos,
		private readonly int|null $targetPos,
		private readonly int|null $moveTimeout,
		private readonly int|null $moveStartedAt,
		private readonly bool $posControl,
		private readonly ActiveEnergyStatusBlock|string $aenergy,
		private readonly TemperatureBlockStatus|string $temperature,
		private readonly array|Utils\ArrayHash $errors = [],
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::COVER);
	}

	public function getSource(): string
	{
		return $this->source;
	}

	public function getState(): Types\CoverPayload|string
	{
		if (Types\CoverPayload::isValidValue($this->state)) {
			return Types\CoverPayload::get($this->state);
		}

		return Shelly\Constants::VALUE_NOT_AVAILABLE;
	}

	public function getActivePower(): float|string
	{
		return $this->apower;
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
		return $this->pf;
	}

	public function getCurrentPosition(): int|null
	{
		return $this->currentPos;
	}

	public function getTargetPosition(): int|null
	{
		return $this->targetPos;
	}

	public function getMoveTimeout(): int|null
	{
		return $this->moveTimeout;
	}

	/**
	 * @throws Exception
	 */
	public function getMoveStartedAt(): DateTimeInterface|null
	{
		if ($this->moveStartedAt !== null) {
			return Utils\DateTime::from($this->moveStartedAt);
		}

		return null;
	}

	public function hasPositionControl(): bool
	{
		return $this->posControl;
	}

	public function getActiveEnergy(): ActiveEnergyStatusBlock|string
	{
		return $this->aenergy;
	}

	public function getTemperature(): TemperatureBlockStatus|string
	{
		return $this->temperature;
	}

	/**
	 * @return array<string>
	 */
	public function getErrors(): array
	{
		return $this->errors instanceof Utils\ArrayHash ? (array) $this->errors : $this->errors;
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
			'state' => $this->getState() instanceof Types\CoverPayload ? $this->getState()->getValue() : null,
			'active_power' => $this->getActivePower(),
			'voltage' => $this->getVoltage(),
			'current' => $this->getCurrent(),
			'power_factor' => $this->getPowerFactor(),
			'current_position' => $this->getCurrentPosition(),
			'target_position' => $this->getTargetPosition(),
			'move_timeout' => $this->getMoveTimeout(),
			'move_started_at' => $this->getMoveStartedAt()?->format(DateTimeInterface::ATOM),
			'has_position_control' => $this->hasPositionControl(),
			'active_energy' => $this->getActiveEnergy() instanceof ActiveEnergyStatusBlock ? $this->getActiveEnergy()->toArray() : null,
			'temperature' => $this->getTemperature() instanceof TemperatureBlockStatus ? $this->getTemperature()->toArray() : null,
			'errors' => $this->getErrors(),
		];
	}

}
