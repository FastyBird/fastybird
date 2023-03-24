<?php declare(strict_types = 1);

/**
 * CombinedEnumFormatItem.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 * @since          0.73.0
 *
 * @date           05.08.22
 */

namespace FastyBird\Library\Metadata\ValueObjects;

use Consistence;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use Nette;
use Nette\Utils;
use function count;
use function explode;
use function floatval;
use function implode;
use function in_array;
use function intval;
use function is_string;
use function strval;
use function trim;

/**
 * Combined enum value format item
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CombinedEnumFormatItem
{

	use Nette\SmartObject;

	private Types\DataTypeShort|null $dataType;

	private string|int|float|bool $value;

	/**
	 * @param string|array<int, string|int|float|bool|null> $item
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(string|array $item)
	{
		$dataType = null;

		if (is_string($item)) {
			if (Utils\Strings::contains($item, '|')) {
				$parts = explode('|', $item) + [null, null];

				if ($parts[0] === null || !Types\DataTypeShort::isValidValue(Utils\Strings::lower($parts[0]))) {
					throw new Exceptions\InvalidArgument('Provided format is not valid for combined enum format');
				}

				$dataType = Types\DataTypeShort::get(Utils\Strings::lower($parts[0]));
				$this->value = trim(strval($parts[1]));
			} else {
				$this->value = trim($item);
			}
		} elseif (count($item) === 2) {
			if ($item[0] !== null) {
				if (!is_string($item[0]) || !Types\DataTypeShort::isValidValue(Utils\Strings::lower($item[0]))) {
					throw new Exceptions\InvalidArgument('Provided format is not valid for combined enum format');
				}

				$dataType = Types\DataTypeShort::get(Utils\Strings::lower($item[0]));
			}

			if ($item[1] === null) {
				throw new Exceptions\InvalidArgument('Provided value is not valid for combined enum format');
			}

			$this->value = is_string($item[1]) ? trim($item[1]) : $item[1];

		} elseif (count($item) === 1) {
			$this->value = trim(strval($item[0]));

		} else {
			throw new Exceptions\InvalidArgument('Provided format is not valid for combined enum format');
		}

		if ($dataType !== null && !$this->validateDataType($dataType)) {
			throw new Exceptions\InvalidArgument('Provided format is not valid for combined enum format');
		}

		$this->dataType = $dataType;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getValue(): float|bool|int|string|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload
	{
		if ($this->dataType === null) {
			return $this->value;
		}

		if (
			$this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_CHAR)
			|| $this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_UCHAR)
			|| $this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_SHORT)
			|| $this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_USHORT)
			|| $this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_INT)
			|| $this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_UINT)
		) {
			return intval($this->value);
		} elseif ($this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_FLOAT)) {
			return floatval($this->value);
		} elseif ($this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_STRING)) {
			return strval($this->value);
		} elseif ($this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_BOOLEAN)) {
			return in_array(Utils\Strings::lower(strval($this->value)), ['true', 't', 'yes', 'y', '1', 'on'], true);
		} elseif ($this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_BUTTON)) {
			if (Types\ButtonPayload::isValidValue(Utils\Strings::lower(strval($this->value)))) {
				return Types\ButtonPayload::get(Utils\Strings::lower(strval($this->value)));
			}

			throw new Exceptions\InvalidState('Combined enum value is not valid');
		} elseif ($this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_SWITCH)) {
			if (Types\SwitchPayload::isValidValue(Utils\Strings::lower(strval($this->value)))) {
				return Types\SwitchPayload::get(Utils\Strings::lower(strval($this->value)));
			}

			throw new Exceptions\InvalidState('Combined enum value is not valid');
		} elseif ($this->dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_COVER)) {
			if (Types\CoverPayload::isValidValue(Utils\Strings::lower(strval($this->value)))) {
				return Types\CoverPayload::get(Utils\Strings::lower(strval($this->value)));
			}

			throw new Exceptions\InvalidState('Combined enum value is not valid');
		}

		throw new Exceptions\InvalidState('Combined enum value is not valid');
	}

	public function getDataType(): Types\DataTypeShort|null
	{
		return $this->dataType;
	}

	/**
	 * @return array<int, string|int|float|bool>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		$value = $this->getValue();

		$flattenValue = $value instanceof Consistence\Enum\Enum ? strval($value->getValue()) : $value;

		if ($this->dataType === null) {
			return [
				strval($this->value),
			];
		}

		return [
			strval($this->dataType->getValue()),
			$flattenValue,
		];
	}

	private function validateDataType(Types\DataTypeShort $dataType): bool
	{
		return in_array($dataType->getValue(), [
			Types\DataTypeShort::DATA_TYPE_CHAR,
			Types\DataTypeShort::DATA_TYPE_UCHAR,
			Types\DataTypeShort::DATA_TYPE_SHORT,
			Types\DataTypeShort::DATA_TYPE_USHORT,
			Types\DataTypeShort::DATA_TYPE_INT,
			Types\DataTypeShort::DATA_TYPE_UINT,
			Types\DataTypeShort::DATA_TYPE_FLOAT,
			Types\DataTypeShort::DATA_TYPE_BOOLEAN,
			Types\DataTypeShort::DATA_TYPE_STRING,
			Types\DataTypeShort::DATA_TYPE_BUTTON,
			Types\DataTypeShort::DATA_TYPE_SWITCH,
		], true);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function __toString(): string
	{
		return implode('|', $this->toArray());
	}

}
