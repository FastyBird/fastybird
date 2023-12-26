<?php declare(strict_types = 1);

/**
 * CompositeExposeData.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Messages;

use Orisai\ObjectMapper;

/**
 * Expose data row
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CompositeExposeData implements Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $parent,
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

	public function getParent(): string
	{
		return $this->parent;
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
			'parent' => $this->getParent(),
			'identifier' => $this->getIdentifier(),
			'value' => $this->getValue(),
		];
	}

}
