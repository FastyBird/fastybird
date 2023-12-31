<?php declare(strict_types = 1);

/**
 * NumericType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Messages\Exposes;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use Orisai\ObjectMapper;
use function array_merge;

/**
 * Numeric type expose type message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NumericType extends Type
{

	public function __construct(
		Types\ExposeType $type,
		string $name,
		string $label,
		string $property,
		int $access,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_min')]
		private readonly float|null $valueMin = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_max')]
		private readonly float|null $valueMax = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_step')]
		private readonly float|null $valueStep = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $unit = null,
	)
	{
		parent::__construct($type, $name, $label, $property, $access);
	}

	public function getValueMin(): float|null
	{
		return $this->valueMin;
	}

	public function getValueMax(): float|null
	{
		return $this->valueMax;
	}

	public function getValueStep(): float|null
	{
		return $this->valueStep;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'value_min' => $this->getValueMin(),
				'value_max' => $this->getValueMax(),
				'value_step' => $this->getValueStep(),
				'unit' => $this->getUnit(),
			],
		);
	}

}