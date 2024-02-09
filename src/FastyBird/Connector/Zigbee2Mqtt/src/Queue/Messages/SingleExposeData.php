<?php declare(strict_types = 1);

/**
 * SingleExposeData.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use Orisai\ObjectMapper;

/**
 * Expose data row
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SingleExposeData implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Types\ExposeDataType::SINGLE])]
		private readonly string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly bool|int|float|string|null $value = null,
	)
	{
	}

	public function getType(): Types\ExposeDataType
	{
		return Types\ExposeDataType::get($this->type);
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getValue(): float|bool|int|string|null
	{
		return $this->value;
	}

	public function toArray(): array
	{
		return [
			'type' => $this->getType()->getValue(),
			'identifier' => $this->getIdentifier(),
			'value' => $this->getValue(),
		];
	}

}
