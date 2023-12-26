<?php declare(strict_types = 1);

/**
 * EnumType.php
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
 * Enum type expose type message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EnumType extends Type
{

	/**
	 * @param array<string> $values
	 */
	public function __construct(
		Types\ExposeType $type,
		string $name,
		string $label,
		string $property,
		int $access,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $values = [],
	)
	{
		parent::__construct($type, $name, $label, $property, $access);
	}

	/**
	 * @return array<string>
	 */
	public function getValues(): array
	{
		return $this->values;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'values' => $this->getValues(),
			],
		);
	}

}
