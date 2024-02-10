<?php declare(strict_types = 1);

/**
 * DeviceSwitchConfiguration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           26.12.22
 */

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;

/**
 * Generation 2 device switch configuration message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceSwitchConfiguration implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['momentary', 'follow', 'flip', 'detached'])]
		#[ObjectMapper\Modifiers\FieldName('in_mode')]
		private readonly string $mode,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['off', 'on', 'restore_last', 'match_input'])]
		#[ObjectMapper\Modifiers\FieldName('initial_state')]
		private readonly string $initialState,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('auto_on')]
		private readonly bool $autoOn,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('auto_on_delay')]
		private readonly float $autoOnDelay,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('auto_off')]
		private readonly bool $autoOff,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('auto_off_delay')]
		private readonly float $autoOffDelay,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(unsigned: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('input_id')]
		private readonly int|null $inputId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('power_limit')]
		private readonly float|null $powerLimit,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('voltage_limit')]
		private readonly float|null $voltageLimit,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('current_limit')]
		private readonly float|null $currentLimit,
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

	public function getInputId(): int|null
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
			'type' => $this->getType()->getValue(),
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
