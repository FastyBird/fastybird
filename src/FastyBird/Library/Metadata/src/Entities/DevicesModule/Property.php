<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
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

	private Types\DataType $dataType;

	private ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null $format;

	private string|int|float|null $invalid;

	/**
	 * @param Array<int, string>|Array<int, string|int|float|Array<int, string|int|float>|null>|Array<int, Array<int, string|Array<int, string|int|float|bool>|null>>|null $format
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		string $id,
		string $type,
		private readonly string $identifier,
		private readonly string|null $name,
		private readonly bool $settable,
		private readonly bool $queryable,
		string $dataType,
		private readonly string|null $unit = null,
		array|null $format = null,
		float|int|string|null $invalid = null,
		private readonly int|null $numberOfDecimals = null,
		string|null $owner = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->type = Types\PropertyType::get($type);
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

	public function getFormat(): ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null
	{
		return $this->format;
	}

	public function getInvalid(): float|int|string|null
	{
		return $this->invalid;
	}

	public function getNumberOfDecimals(): int|null
	{
		return $this->numberOfDecimals;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => $this->getType()->getValue(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'queryable' => $this->isQueryable(),
			'settable' => $this->isSettable(),
			'data_type' => $this->getDataType()->getValue(),
			'unit' => $this->getUnit(),
			'format' => $this->getFormat()?->toArray(),
			'invalid' => $this->getInvalid(),
			'number_of_decimals' => $this->getNumberOfDecimals(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

	/**
	 * @param Array<int, string>|Array<int, string|int|float|Array<int, string|int|float>|null>|Array<int, Array<int, string|Array<int, string|int|float|bool>|null>>|null $format
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function buildFormat(
		array|null $format,
	): ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null
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
			$format = implode(':', array_map(static function ($item): string {
				if (is_array($item)) {
					return implode(
						'|',
						array_map(
							static fn ($part): string|int|float => is_array($part) ? strval($part) : $part,
							$item,
						),
					);
				}

				return strval($item);
			}, $format));

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
			$format = implode(',', array_map(static function ($item): string {
				if (is_array($item)) {
					return (is_array($item[0]) ? implode('|', $item[0]) : $item[0])
						. ':' . (is_array($item[1]) ? implode('|', $item[1]) : ($item[1] ?? ''))
						. (
							isset($item[2]) ?
								':' . (is_array($item[2]) ? implode('|', $item[2]) : $item[2]) : ''
						);
				}

				return strval($item);
			}, $format));

			if (preg_match(Metadata\Constants::VALUE_FORMAT_STRING_ENUM, $format) === 1) {
				return new ValueObjects\StringEnumFormat($format);
			} elseif (preg_match(Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM, $format) === 1) {
				return new ValueObjects\CombinedEnumFormat($format);
			}
		}

		return null;
	}

	/**
	 * @return Array<string, mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
