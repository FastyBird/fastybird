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
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Orisai\ObjectMapper;
use function array_merge;
use function is_bool;

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
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Types\ExposeType::BINARY])]
		private readonly string $type,
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
		parent::__construct($name, $label, $property, $access);
	}

	public function getType(): Types\ExposeType
	{
		return Types\ExposeType::get($this->type);
	}

	public function getDataType(): MetadataTypes\DataType
	{
		if (is_bool($this->getValueOn()) && is_bool($this->getValueOff())) {
			return MetadataTypes\DataType::BOOLEAN;
		}

		return MetadataTypes\DataType::SWITCH;
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

	public function getFormat(): array|null
	{
		if (is_bool($this->getValueOn()) && is_bool($this->getValueOff())) {
			return parent::getFormat();
		}

		return array_merge(
			[
				[
					MetadataTypes\Payloads\Switcher::ON,
					$this->getValueOn(),
					$this->getValueOn(),
				],
				[
					MetadataTypes\Payloads\Switcher::OFF,
					$this->getValueOff(),
					$this->getValueOff(),
				],
			],
			[
				$this->getValueToggle() !== null
					? [
						MetadataTypes\Payloads\Switcher::TOGGLE,
						$this->getValueToggle(),
						$this->getValueToggle(),
					]
					: [],
			],
		);
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
