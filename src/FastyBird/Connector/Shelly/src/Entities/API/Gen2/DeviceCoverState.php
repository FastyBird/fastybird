<?php declare(strict_types = 1);

/**
 * DeviceCoverState.php
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
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Nette\Utils;
use Orisai\ObjectMapper;
use function intval;

/**
 * Generation 2 device cover state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceCoverState implements Entities\API\Entity
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $source,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\CoverPayload::class)]
		private readonly Types\CoverPayload $state,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('apower')]
		private readonly float|string $activePower,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly float|string $voltage,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly float|string $current,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('pf')]
		private readonly float|string $powerFactor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('freq')]
		private readonly float|string $frequency,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: ActiveEnergyStateBlock::class),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('aenergy')]
		private readonly ActiveEnergyStateBlock|string $activeEnergy,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('current_pos')]
		private readonly int|null $currentPosition,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('target_pos')]
		private readonly int|null $targetPosition,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('move_timeout')]
		private readonly float|null $moveTimeout,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('move_started_at')]
		private readonly float|null $moveStartedAt,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('pos_control')]
		private readonly bool $hasPositionControl,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: TemperatureBlockState::class),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly TemperatureBlockState|string $temperature,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
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
		return Types\ComponentType::get(Types\ComponentType::COVER);
	}

	public function getSource(): string
	{
		return $this->source;
	}

	public function getState(): Types\CoverPayload
	{
		return $this->state;
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

	public function getFrequency(): float|string
	{
		return $this->frequency;
	}

	public function getCurrentPosition(): int|null
	{
		return $this->currentPosition;
	}

	public function getTargetPosition(): int|null
	{
		return $this->targetPosition;
	}

	public function getMoveTimeout(): float|null
	{
		return $this->moveTimeout;
	}

	/**
	 * @throws Exception
	 */
	public function getMoveStartedAt(): DateTimeInterface|null
	{
		if ($this->moveStartedAt !== null) {
			return Utils\DateTime::from(intval($this->moveStartedAt));
		}

		return null;
	}

	public function hasPositionControl(): bool
	{
		return $this->hasPositionControl;
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
			'state' => $this->getState()->getValue(),
			'active_power' => $this->getActivePower(),
			'voltage' => $this->getVoltage(),
			'current' => $this->getCurrent(),
			'power_factor' => $this->getPowerFactor(),
			'current_position' => $this->getCurrentPosition(),
			'target_position' => $this->getTargetPosition(),
			'move_timeout' => $this->getMoveTimeout(),
			'move_started_at' => $this->getMoveStartedAt()?->format(DateTimeInterface::ATOM),
			'has_position_control' => $this->hasPositionControl(),
			'active_energy' => $this->getActiveEnergy() instanceof ActiveEnergyStateBlock ? $this->getActiveEnergy()->toArray() : null,
			'temperature' => $this->getTemperature() instanceof TemperatureBlockState ? $this->getTemperature()->toArray() : null,
			'errors' => $this->getErrors(),
		];
	}

}
