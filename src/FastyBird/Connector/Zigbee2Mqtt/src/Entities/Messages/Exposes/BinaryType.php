<?php declare(strict_types = 1);

/**
 * Type.php
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
 * Binary type expose type message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class BinaryType extends Type
{

	public function __construct(
		Types\ExposeType $type,
		string $name,
		string $label,
		string $property,
		int $access,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_on')]
		private readonly bool|string $valueOn,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_off')]
		private readonly bool|string $valueOff,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_toggle')]
		private readonly bool|string|null $valueToggle = null,
	)
	{
		parent::__construct($type, $name, $label, $property, $access);
	}

	public function getValueOn(): bool|string
	{
		return $this->valueOn;
	}

	public function getValueOff(): bool|string
	{
		return $this->valueOff;
	}

	public function getValueToggle(): bool|string|null
	{
		return $this->valueToggle;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'value_on' => $this->getValueOn(),
				'value_off' => $this->getValueOff(),
				'value_toggle' => $this->getValueToggle(),
			],
		);
	}

}
