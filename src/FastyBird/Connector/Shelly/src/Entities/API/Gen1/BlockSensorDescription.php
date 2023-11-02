<?php declare(strict_types = 1);

/**
 * BlockSensorDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Orisai\ObjectMapper;

/**
 * Block sensor description entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class BlockSensorDescription implements Entities\API\Entity
{

	/**
	 * @param array<string>|array<int>|array<float>|array<int, array<int, (array<int, bool|string>|null)>>|null $format
	 */
	public function __construct(
		#[ObjectMapper\Rules\IntValue()]
		private readonly int $identifier,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\SensorType::class)]
		private readonly Types\SensorType $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $description,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: MetadataTypes\DataType::class)]
		#[ObjectMapper\Modifiers\FieldName('data_type')]
		private readonly MetadataTypes\DataType $dataType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $unit = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\StringValue(notEmpty: true)),
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\IntValue()),
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\FloatValue()),
			new ObjectMapper\Rules\ArrayOf(
				new ObjectMapper\Rules\ArrayOf(
					new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\ArrayOf(
							new ObjectMapper\Rules\AnyOf([
								new ObjectMapper\Rules\BoolValue(castBoolLike: true),
								new ObjectMapper\Rules\StringValue(notEmpty: true),
								new ObjectMapper\Rules\NullValue(castEmptyString: true),
							]),
							new ObjectMapper\Rules\IntValue(),
						),
						new ObjectMapper\Rules\NullValue(),
					]),
					new ObjectMapper\Rules\IntValue(),
				),
				new ObjectMapper\Rules\IntValue(),
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly array|null $format = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly float|int|string|null $invalid = null,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private readonly bool $queryable = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private readonly bool $settable = false,
	)
	{
	}

	public function getIdentifier(): int
	{
		return $this->identifier;
	}

	public function getType(): Types\SensorType
	{
		return $this->type;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	/**
	 * @return array<string>|array<int>|array<float>|array<int, array<int, (array<int, bool|string>|null)>>|null
	 */
	public function getFormat(): mixed
	{
		return $this->format;
	}

	public function getInvalid(): float|int|string|null
	{
		return $this->invalid;
	}

	public function isQueryable(): bool
	{
		return $this->queryable;
	}

	public function isSettable(): bool
	{
		return $this->settable;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'type' => $this->getType()->getValue(),
			'description' => $this->getDescription(),
			'data_type' => $this->getDataType()->getValue(),
			'unit' => $this->getUnit(),
			'format' => $this->getFormat(),
			'invalid' => $this->getInvalid(),
			'queryable' => $this->isQueryable(),
			'settable' => $this->isQueryable(),
		];
	}

}
