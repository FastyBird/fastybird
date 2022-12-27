<?php declare(strict_types = 1);

/**
 * DeviceSwitchConfiguration.php
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

use FastyBird\Connector\Shelly\Entities\API\Entity;
use Nette;

/**
 * Generation 2 device switch configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceSwitchConfiguration implements Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $id,
		private readonly string|null $name,
		private readonly string $inMode,
		private readonly string $initialState,
		private readonly bool $autoOn,
		private readonly float $autoOnDelay,
		private readonly bool $autoOff,
		private readonly float $autoOffDelay,
		private readonly float|null $inputId,
		private readonly float|null $powerLimit,
		private readonly float|null $voltageLimit,
		private readonly float|null $currentLimit,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getMode(): string
	{
		return $this->inMode;
	}

	public function getInitialState(): string
	{
		return $this->initialState;
	}

	public function hasAutoOn(): bool
	{
		return $this->autoOn;
	}

	public function getAutoOnDelay(): float
	{
		return $this->autoOnDelay;
	}

	public function hasAutoOff(): bool
	{
		return $this->autoOff;
	}

	public function getAutoOffDelay(): float
	{
		return $this->autoOffDelay;
	}

	public function getInputId(): float|null
	{
		return $this->inputId;
	}

	public function getPowerLimit(): float|null
	{
		return $this->powerLimit;
	}

	public function getVoltageLimit(): float|null
	{
		return $this->voltageLimit;
	}

	public function getCurrentLimit(): float|null
	{
		return $this->currentLimit;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'mode' => $this->getMode(),
			'initial_state' => $this->getInitialState(),
			'auto_on' => $this->hasAutoOn(),
			'auto_on_delay' => $this->getAutoOnDelay(),
			'auto_off' => $this->hasAutoOff(),
			'auto_off_delay' => $this->getAutoOffDelay(),
			'input_id' => $this->getInputId(),
			'power_limit' => $this->getPowerLimit(),
			'voltage_limit' => $this->getVoltageLimit(),
			'current_limit' => $this->getCurrentLimit(),
		];
	}

}
