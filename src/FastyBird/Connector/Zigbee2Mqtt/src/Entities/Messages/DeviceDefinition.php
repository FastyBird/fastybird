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
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly string $model,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $vendor,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $description,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\BinaryType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\EnumType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\NumericType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\TextType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\CompositeType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Entities\Messages\Exposes\ListType::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $exposes,
	)
	{
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getVendor(): string
	{
		return $this->vendor;
	}

	public function getDescription(): string
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
		];
	}

}
