<?php declare(strict_types = 1);

/**
 * NumberRangeFormat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     ValueObjects
 * @since          0.73.0
 *
 * @date           05.08.22
 */

namespace FastyBird\Metadata\ValueObjects;

use FastyBird\Metadata\Exceptions;
use FastyBird\Metadata\Types;
use Nette;
use Nette\Utils;
use function array_map;
use function count;
use function explode;
use function floatval;
use function implode;
use function intval;
use function is_array;
use function is_numeric;
use function is_string;
use function strval;
use function trim;

/**
 * String enum value format
 *
 * @package        FastyBird:Metadata!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NumberRangeFormat
{

	use Nette\SmartObject;

	private int|float|null $min = null;

	private int|float|null $max = null;

	private Types\DataTypeShort|null $minDataType = null;

	private Types\DataTypeShort|null $maxDataType = null;

	/**
	 * @param string|Array<int, string|int|float|Array<int, string|int|float>|null> $format
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(string|array $format)
	{
		if (is_string($format) && Utils\Strings::contains($format, ':')) {
			$items = explode(':', $format) + [null, null];

			if (is_string($items[0])) {
				if (Utils\Strings::contains($items[0], '|')) {
					$parts = explode('|', $items[0]) + [null, null];

					if (
						$parts[0] === null
						|| !Types\DataTypeShort::isValidValue(Utils\Strings::lower($parts[0]))
					) {
						throw new Exceptions\InvalidArgument('Provided format is not valid for number range format');
					}

					$this->min = $parts[1] !== null && trim($parts[1]) !== '' ? floatval($parts[1]) : null;
					$this->minDataType = Types\DataTypeShort::get(Utils\Strings::lower($parts[0]));

				} elseif (trim($items[0]) !== '') {
					$this->min = floatval($items[0]);
					$this->minDataType = null;

				} else {
					$this->min = null;
					$this->minDataType = null;
				}
			} else {
				$this->min = null;
			}

			if (is_string($items[1])) {
				if (Utils\Strings::contains($items[1], '|')) {
					$parts = explode('|', $items[1]) + [null, null];

					if (
						$parts[0] === null
						|| !Types\DataTypeShort::isValidValue(Utils\Strings::lower($parts[0]))
					) {
						throw new Exceptions\InvalidArgument('Provided format is not valid for number range format');
					}

					$this->max = $parts[1] !== null && trim($parts[1]) !== '' ? floatval($parts[1]) : null;
					$this->maxDataType = Types\DataTypeShort::get(Utils\Strings::lower($parts[0]));

				} elseif (trim($items[1]) !== '') {
					$this->max = floatval($items[1]);
					$this->maxDataType = null;

				} else {
					$this->max = null;
					$this->maxDataType = null;
				}
			} else {
				$this->max = null;
			}
		} elseif (is_array($format) && count($format) === 2) {
			foreach ($format as $item) {
				if (!$this->checkItem($item)) {
					throw new Exceptions\InvalidArgument('Provided format is not valid for number range format');
				}
			}

			if (is_array($format[0]) && count($format[0]) === 2) {
				$this->minDataType = Types\DataTypeShort::get(Utils\Strings::lower(strval($format[0][0])));
				$this->min = is_numeric($format[0][1]) ? floatval($format[0][1]) : null;
			} else {
				$this->min = is_numeric($format[0]) ? floatval($format[0]) : null;
			}

			if (is_array($format[1]) && count($format[1]) === 2) {
				$this->maxDataType = Types\DataTypeShort::get(Utils\Strings::lower(strval($format[1][0])));
				$this->max = is_numeric($format[1][1]) ? floatval($format[1][1]) : null;
			} else {
				$this->max = is_numeric($format[1]) ? floatval($format[1]) : null;
			}
		} else {
			throw new Exceptions\InvalidArgument('Provided format is not valid for number range format');
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getMin(): float|int|null
	{
		if ($this->getMinDataType() !== null) {
			if (
				$this->getMinDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_CHAR)
				|| $this->getMinDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_UCHAR)
				|| $this->getMinDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_SHORT)
				|| $this->getMinDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_USHORT)
				|| $this->getMinDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_INT)
				|| $this->getMinDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_UINT)
			) {
				return intval($this->min);
			} elseif ($this->getMinDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_FLOAT)) {
				return floatval($this->min);
			}

			throw new Exceptions\InvalidState('Value is in invalid state');
		}

		return $this->min;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getMax(): float|int|null
	{
		if ($this->getMaxDataType() !== null) {
			if (
				$this->getMaxDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_CHAR)
				|| $this->getMaxDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_UCHAR)
				|| $this->getMaxDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_SHORT)
				|| $this->getMaxDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_USHORT)
				|| $this->getMaxDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_INT)
				|| $this->getMaxDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_UINT)
			) {
				return intval($this->max);
			} elseif ($this->getMaxDataType()->equalsValue(Types\DataTypeShort::DATA_TYPE_FLOAT)) {
				return floatval($this->max);
			}

			throw new Exceptions\InvalidState('Value is in invalid state');
		}

		return $this->max;
	}

	public function getMinDataType(): Types\DataTypeShort|null
	{
		return $this->min !== null ? $this->minDataType : null;
	}

	public function getMaxDataType(): Types\DataTypeShort|null
	{
		return $this->max !== null ? $this->maxDataType : null;
	}

	/**
	 * @return Array<int, int|float|Array<int, string|int|float|null>|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return [
			($this->getMinDataType() !== null ? [strval(
				$this->getMinDataType()->getValue(),
			), $this->getMin()] : $this->getMin()),
			($this->getMaxDataType() !== null ? [strval(
				$this->getMaxDataType()->getValue(),
			), $this->getMax()] : $this->getMax()),
		];
	}

	/**
	 * @param int|float|Array<int, string|int|float>|null $item
	 */
	private function checkItem(string|int|float|array|null $item): bool
	{
		return (
				is_array($item)
				&& count($item) === 2
				&& is_string($item[0])
				&& Types\DataTypeShort::isValidValue(Utils\Strings::lower($item[0]))
				&& is_numeric($item[1])
			)
			|| is_numeric($item)
			|| is_string($item)
			|| $item === null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function __toString(): string
	{
		return implode(':', array_map(static function (int|float|array|null $item): string|int|float|null {
			if (is_array($item)) {
				return implode('|', $item);
			}

			return $item;
		}, $this->toArray()));
	}

}
