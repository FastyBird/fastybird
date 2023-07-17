<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\DevicesModule;

use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\ValueObjects;
use Ramsey\Uuid;
use function array_map;
use function implode;
use function in_array;
use function is_array;
use function preg_match;
use function strval;

/**
 * Property entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Property implements Entities\Entity, Entities\Owner
{

	use Entities\TOwner;

	private Uuid\UuidInterface $id;

	private Types\PropertyType $type;

	private Types\PropertyCategory $category;

	private Types\DataType $dataType;

	private ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|ValueObjects\EquationFormat|null $format;

	private string|int|float|null $invalid;

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|string|null $format
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		string $id,
		string $type,
		string $category,
		private readonly string $identifier,
		private readonly string|null $name,
		private readonly bool $settable,
		private readonly bool $queryable,
		string $dataType,
		private readonly string|null $unit = null,
		array|string|null $format = null,
		float|int|string|null $invalid = null,
		private readonly int|null $scale = null,
		private readonly int|null $step = null,
		string|null $owner = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->type = Types\PropertyType::get($type);
		$this->category = Types\PropertyCategory::get($category);
		$this->dataType = Types\DataType::get($dataType);
		$this->invalid = $invalid;
		$this->owner = $owner !== null ? Uuid\Uuid::fromString($owner) : null;

		$this->format = $this->buildFormat($format);
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getType(): Types\PropertyType
	{
		return $this->type;
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

	public function isSettable(): bool
	{
		return $this->settable;
	}

	public function isQueryable(): bool
	{
		return $this->queryable;
	}

	public function getDataType(): Types\DataType
	{
		return $this->dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getFormat(): ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|ValueObjects\EquationFormat|null
	{
		return $this->format;
	}

	public function getInvalid(): float|int|string|null
	{
		return $this->invalid;
	}

	public function getScale(): int|null
	{
		return $this->scale;
	}

	public function getStep(): int|null
	{
		return $this->step;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => $this->getType()->getValue(),
			'category' => $this->getCategory()->getValue(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'queryable' => $this->isQueryable(),
			'settable' => $this->isSettable(),
			'data_type' => $this->getDataType()->getValue(),
			'unit' => $this->getUnit(),
			'format' => $this->getFormat()?->getValue(),
			'invalid' => $this->getInvalid(),
			'scale' => $this->getScale(),
			'step' => $this->getStep(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|string|null $format
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
								static fn ($part): string|int|float|null => is_array($part) ? implode($part) : $part,
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

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
