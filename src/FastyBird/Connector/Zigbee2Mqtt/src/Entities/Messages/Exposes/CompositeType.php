<?php declare(strict_types = 1);

/**
 * CompositeType.php
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

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Messages\Exposes;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use Orisai\ObjectMapper;
use function array_map;
use function array_merge;

/**
 * Composite type expose type message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CompositeType extends Type
{

	/**
	 * @param array<BinaryType|EnumType|NumericType|TextType|CompositeType> $features
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Types\ExposeType::COMPOSITE])]
		private readonly string $type,
		string $name,
		string $label,
		string $property,
		int $access,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: BinaryType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: EnumType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: NumericType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: TextType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: self::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $features,
	)
	{
		parent::__construct($name, $label, $property, $access);
	}

	public function getType(): Types\ExposeType
	{
		return Types\ExposeType::get($this->type);
	}

	/**
	 * @return array<BinaryType|EnumType|NumericType|TextType|CompositeType>
	 */
	public function getFeatures(): array
	{
		return $this->features;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'features' => array_map(static fn (Type $expose): array => $expose->toArray(), $this->getFeatures()),
			],
		);
	}

}
