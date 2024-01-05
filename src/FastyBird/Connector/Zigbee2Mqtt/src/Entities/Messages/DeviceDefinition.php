<?php declare(strict_types = 1);

/**
 * DeviceDefinition.php
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

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Device definition message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceDefinition implements Entity
{

	/**
	 * @param array<Entities\Messages\Exposes\Type> $exposes
	 */
	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $model,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $vendor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $description,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\BinaryType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\EnumType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\NumericType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\TextType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\CompositeType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\ListType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\ClimateType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\CoverType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\FanType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\LightType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\LockType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\SwitchType::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $exposes,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('supports_ota')]
		private readonly bool $supportsOta,
	)
	{
	}

	public function getModel(): string|null
	{
		return $this->model;
	}

	public function getVendor(): string|null
	{
		return $this->vendor;
	}

	public function getDescription(): string|null
	{
		return $this->description;
	}

	/**
	 * @return array<Entities\Messages\Exposes\Type>
	 */
	public function getExposes(): array
	{
		return $this->exposes;
	}

	public function doesSupportsOta(): bool
	{
		return $this->supportsOta;
	}

	public function toArray(): array
	{
		return [
			'model' => $this->getModel(),
			'vendor' => $this->getVendor(),
			'description' => $this->getDescription(),
			'exposes' => array_map(
				static fn (Entities\Messages\Exposes\Type $expose): array => $expose->toArray(),
				$this->getExposes(),
			),
			'supports_ota' => $this->doesSupportsOta(),
		];
	}

}
