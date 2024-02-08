<?php declare(strict_types = 1);

/**
 * DeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           27.11.23
 */

namespace FastyBird\Library\Metadata\Documents\DevicesModule;

use DateTimeInterface;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Formats;
use FastyBird\Library\Metadata\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function implode;
use function in_array;
use function is_array;
use function preg_match;
use function strval;

/**
 * Device property document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class DeviceProperty implements Documents\Document, Documents\Owner
{

	use Documents\TOwner;
	use Documents\TCreatedAt;
	use Documents\TUpdatedAt;

	/**
	 * @param string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\PropertyCategory::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\PropertyCategory::class),
		])]
		private readonly Types\PropertyCategory $category,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\DataType::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\DataType::class),
		])]
		#[ObjectMapper\Modifiers\FieldName('data_type')]
		private readonly Types\DataType $dataType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $unit = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\StringValue(notEmpty: true),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
			),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\ArrayOf(
					item: new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\IntValue(),
						new ObjectMapper\Rules\FloatValue(),
						new ObjectMapper\Rules\StringValue(notEmpty: true),
						new ObjectMapper\Rules\ArrayOf(
							item: new ObjectMapper\Rules\AnyOf([
								new ObjectMapper\Rules\ArrayEnumValue(
								// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									cases: [Types\DataTypeShort::CHAR, Types\DataTypeShort::UCHAR, Types\DataTypeShort::SHORT, Types\DataTypeShort::USHORT, Types\DataTypeShort::INT, Types\DataTypeShort::UINT, Types\DataTypeShort::FLOAT, Types\DataTypeShort::BOOLEAN, Types\DataTypeShort::STRING, Types\DataTypeShort::BUTTON, Types\DataTypeShort::SWITCH, Types\DataTypeShort::COVER],
								),
								new ObjectMapper\Rules\StringValue(notEmpty: true),
								new ObjectMapper\Rules\IntValue(),
								new ObjectMapper\Rules\FloatValue(),
								new ObjectMapper\Rules\BoolValue(),
							]),
							key: new ObjectMapper\Rules\IntValue(unsigned: true),
							minItems: 2,
							maxItems: 2,
						),
						new ObjectMapper\Rules\NullValue(castEmptyString: true),
					]),
					key: new ObjectMapper\Rules\IntValue(unsigned: true),
					minItems: 3,
					maxItems: 3,
				),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
			),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\AnyOf([
					new ObjectMapper\Rules\IntValue(),
					new ObjectMapper\Rules\FloatValue(),
					new ObjectMapper\Rules\ArrayOf(
						item: new ObjectMapper\Rules\AnyOf([
							new ObjectMapper\Rules\ArrayEnumValue(
							// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
								cases: [Types\DataTypeShort::CHAR, Types\DataTypeShort::UCHAR, Types\DataTypeShort::SHORT, Types\DataTypeShort::USHORT, Types\DataTypeShort::INT, Types\DataTypeShort::UINT, Types\DataTypeShort::FLOAT],
							),
							new ObjectMapper\Rules\IntValue(),
							new ObjectMapper\Rules\FloatValue(),
						]),
						key: new ObjectMapper\Rules\IntValue(unsigned: true),
						minItems: 2,
						maxItems: 2,
					),
					new ObjectMapper\Rules\NullValue(castEmptyString: true),
				]),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
				minItems: 2,
				maxItems: 2,
			),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|array|null $format = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly float|int|string|null $invalid = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|null $scale = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|float|null $step = null,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\Rules\UuidValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_transformer')]
		private readonly Uuid\UuidInterface|string|null $valueTransformer = null,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		protected readonly Uuid\UuidInterface|null $owner = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('created_at')]
		private readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('updated_at')]
		private readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	abstract public function getType(): string;

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getCategory(): Types\PropertyCategory
	{
		return $this->category;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getDataType(): Types\DataType
	{
		return $this->dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getFormat(): Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null
	{
		return $this->buildFormat($this->format);
	}

	public function getInvalid(): float|int|string|null
	{
		return $this->invalid;
	}

	public function getScale(): int|null
	{
		return $this->scale;
	}

	public function getStep(): int|float|null
	{
		return $this->step;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getValueTransformer(): Uuid\UuidInterface|string|null
	{
		if ($this->valueTransformer === null) {
			return null;
		}

		if ($this->valueTransformer instanceof Uuid\UuidInterface) {
			return $this->valueTransformer;
		}

		if (preg_match(Metadata\Constants::VALUE_EQUATION_TRANSFORMER, $this->valueTransformer) === 1) {
			if (
				in_array(
					$this->dataType,
					[
						Types\DataType::CHAR,
						Types\DataType::UCHAR,
						Types\DataType::SHORT,
						Types\DataType::USHORT,
						Types\DataType::INT,
						Types\DataType::UINT,
						Types\DataType::FLOAT,
					],
					true,
				)
			) {
				return $this->valueTransformer;
			}

			throw new Exceptions\InvalidState('Equation transformer is allowed only for numeric data type');
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'category' => $this->getCategory()->value,
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'data_type' => $this->getDataType()->value,
			'unit' => $this->getUnit(),
			'format' => $this->getFormat()?->getValue(),
			'invalid' => $this->getInvalid(),
			'scale' => $this->getScale(),
			'step' => $this->getStep(),
			'value_transformer' => $this->getValueTransformer() !== null ? strval($this->getValueTransformer()) : null,

			'device' => $this->getDevice()->toString(),
			'owner' => $this->getOwner()?->toString(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	/**
	 * @param string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function buildFormat(
		array|string|null $format,
	): Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null
	{
		if ($format === null) {
			return null;
		}

		if (
			in_array(
				$this->dataType,
				[
					Types\DataType::CHAR,
					Types\DataType::UCHAR,
					Types\DataType::SHORT,
					Types\DataType::USHORT,
					Types\DataType::INT,
					Types\DataType::UINT,
					Types\DataType::FLOAT,
				],
				true,
			)
		) {
			if (is_array($format)) {
				$format = implode(':', array_map(static function ($item): string {
					if (is_array($item)) {
						return implode(
							'|',
							array_map(
								static fn ($part): bool|int|float|string|null => is_array($part) ? implode(
									$part,
								) : $part,
								$item,
							),
						);
					}

					return strval($item);
				}, $format));
			}

			if (preg_match(Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE, $format) === 1) {
				return new Formats\NumberRange($format);
			}
		} elseif (
			in_array(
				$this->dataType,
				[
					Types\DataType::ENUM,
					Types\DataType::BUTTON,
					Types\DataType::SWITCH,
					Types\DataType::COVER,
				],
				true,
			)
		) {
			if (is_array($format)) {
				$format = implode(',', array_map(static function ($item): string {
					if (is_array($item)) {
						return (is_array($item[0]) ? implode('|', $item[0]) : $item[0])
							. ':' . (is_array($item[1]) ? implode('|', $item[1]) : ($item[1] ?? ''))
							. (
							isset($item[2])
								? ':' . (is_array($item[2]) ? implode('|', $item[2]) : $item[2])
								: ''
							);
					}

					return strval($item);
				}, $format));
			}

			if (preg_match(Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM, $format) === 1) {
				return new Formats\CombinedEnum($format);
			} elseif (preg_match(Metadata\Constants::VALUE_FORMAT_STRING_ENUM, $format) === 1) {
				return new Formats\StringEnum($format);
			}
		}

		return null;
	}

}
