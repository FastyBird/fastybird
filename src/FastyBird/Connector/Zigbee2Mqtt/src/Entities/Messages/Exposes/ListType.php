<?php declare(strict_types = 1);

/**
 * ListType.php
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
 * List type expose type message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ListType extends Type
{

	public function __construct(
		Types\ExposeType $type,
		string $name,
		string $label,
		string $property,
		int $access,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('length_min')]
		private readonly int|null $lengthMin,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('length_max')]
		private readonly int|null $length_max,
	)
	{
		parent::__construct($type, $name, $label, $property, $access);
	}

	public function getLengthMin(): int|null
	{
		return $this->lengthMin;
	}

	public function getLengthMax(): int|null
	{
		return $this->length_max;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'length_min' => $this->getLengthMin(),
				'length_max' => $this->getLengthMax(),
			],
		);
	}

}