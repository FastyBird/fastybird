<?php declare(strict_types = 1);

/**
 * ConnectorProperty.php
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

use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\ValueObjects;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function implode;
use function in_array;
use function is_array;
use function preg_match;
use function strval;

/**
 * Connector property document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class ConnectorProperty implements Documents\Document, Documents\Owner
{

	use Documents\TOwner;

	/**
	 * @param string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\PropertyCategory::class)]
		private readonly Types\PropertyCategory $category,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\DataType::class)]
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
									cases: [Types\DataTypeShort::DATA_TYPE_CHAR, Types\DataTypeShort::DATA_TYPE_UCHAR, Types\DataTypeShort::DATA_TYPE_SHORT, Types\DataTypeShort::DATA_TYPE_USHORT, Types\DataTypeShort::DATA_TYPE_INT, Types\DataTypeShort::DATA_TYPE_UINT, Types\DataTypeShort::DATA_TYPE_FLOAT, Types\DataTypeShort::DATA_TYPE_BOOLEAN, Types\DataTypeShort::DATA_TYPE_STRING, Types\DataTypeShort::DATA_TYPE_BUTTON, Types\DataTypeShort::DATA_TYPE_SWITCH, Types\DataTypeShort::DATA_TYPE_COVER],
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
								cases: [Types\DataTypeShort::DATA_TYPE_CHAR, Types\DataTypeShort::DATA_TYPE_UCHAR, Types\DataTypeShort::DATA_TYPE_SHORT, Types\DataTypeShort::DATA_TYPE_USHORT, Types\DataTypeShort::DATA_TYPE_INT, Types\DataTypeShort::DATA_TYPE_UINT, Types\DataTypeShort::DATA_TYPE_FLOAT],
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
			new BootstrapObjectMapper\Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		protected readonly Uuid\UuidInterface|null $owner = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	abstract public function getType(): Types\PropertyType;

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
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
	public function getFormat(): ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|ValueObjects\EquationFormat|null
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'category' => $this->getCategory()->getValue(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'data_type' => $this->getDataType()->getValue(),
			'unit' => $this->getUnit(),
			'format' => $this->getFormat()?->getValue(),
			'invalid' => $this->getInvalid(),
			'scale' => $this->getScale(),
			'step' => $this->getStep(),

			'connector' => $this->getConnector()->toString(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

	/**
	 * @param string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function buildFormat(
		array|string|null $format,
	): ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|ValueObjects\EquationFormat|null
	{
		if ($format === null) {
			return null;
		}

		if (
			in_array($this->dataType->getValue(), [
				Types\DataType::DATA_TYPE_CHAR,
				Types\DataType::DATA_TYPE_UCHAR,
				Types\DataType::DATA_TYPE_SHORT,
				Types\DataType::DATA_TYPE_USHORT,
				Types\DataType::DATA_TYPE_INT,
				Types\DataType::DATA_TYPE_UINT,
				Types\DataType::DATA_TYPE_FLOAT,
			], true)
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
			} elseif (preg_match(Metadata\Constants::VALUE_FORMAT_EQUATION, $format) === 1) {
				return new ValueObjects\EquationFormat($format);
			}

			if (preg_match(Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE, $format) === 1) {
				return new ValueObjects\NumberRangeFormat($format);
			}
		} elseif (
			in_array($this->dataType->getValue(), [
				Types\DataType::DATA_TYPE_ENUM,
				Types\DataType::DATA_TYPE_BUTTON,
				Types\DataType::DATA_TYPE_SWITCH,
				Types\DataType::DATA_TYPE_COVER,
			], true)
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

			if (preg_match(Metadata\Constants::VALUE_FORMAT_STRING_ENUM, $format) === 1) {
				return new ValueObjects\StringEnumFormat($format);
			} elseif (preg_match(Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM, $format) === 1) {
				return new ValueObjects\CombinedEnumFormat($format);
			}
		}

		return null;
	}

}
