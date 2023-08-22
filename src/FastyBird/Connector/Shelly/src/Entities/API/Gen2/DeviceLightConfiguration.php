<?php declare(strict_types = 1);

/**
 * DeviceLightConfiguration.php
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
 * Generation 2 device light configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceLightConfiguration implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $id,
		private readonly string|null $name,
		private readonly string $initialState,
		private readonly bool $autoOn,
		private readonly int $autoOnDelay,
		private readonly bool $autoOff,
		private readonly int $autoOffDelay,
		private readonly LightDefaultConfigurationBlock|null $default,
		private readonly LightNightModeConfigurationBlock|null $nightMode,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::get(Types\ComponentType::LIGHT);
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getInitialState(): string
	{
		return $this->initialState;
	}

	public function hasAutoOn(): bool
	{
		return $this->autoOn;
	}

	public function getAutoOnDelay(): int
	{
		return $this->autoOnDelay;
	}

	public function hasAutoOff(): bool
	{
		return $this->autoOff;
	}

	public function getAutoOffDelay(): int
	{
		return $this->autoOffDelay;
	}

	public function getDefault(): LightDefaultConfigurationBlock|null
	{
		return $this->default;
	}

	public function getNightMode(): LightNightModeConfigurationBlock|null
	{
		return $this->nightMode;
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
			'initial_state' => $this->getInitialState(),
			'auto_on' => $this->hasAutoOn(),
			'auto_on_delay' => $this->getAutoOnDelay(),
			'auto_off' => $this->hasAutoOff(),
			'auto_off_delay' => $this->getAutoOffDelay(),
			'default' => $this->getDefault()?->toArray(),
			'night_mode' => $this->getNightMode()?->toArray(),
		];
	}

}
