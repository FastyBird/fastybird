<?php declare(strict_types = 1);

/**
 * Value.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           05.12.20
 */

namespace FastyBird\Library\Metadata\Utilities;

use Consistence;
use DateTime;
use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\Types\Payloads\Button;
use FastyBird\Library\Metadata\Types\Payloads\Cover;
use FastyBird\Library\Metadata\Types\Payloads\Switcher;
use FastyBird\Library\Metadata\ValueObjects;
use Nette\Utils;
use function array_filter;
use function array_values;
use function count;
use function floatval;
use function implode;
use function in_array;
use function intval;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function round;
use function sprintf;
use function strval;

/**
 * Value helpers
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Value
{

	private const DATE_FORMAT = 'Y-m-d';

	private const TIME_FORMAT = 'H:i:sP';

	private const BOOL_TRUE_VALUES = ['true', 't', 'yes', 'y', '1', 'on'];

	/**
	 * Purpose of this method is to convert value to defined data type
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\InvalidValue
	 */
	public static function normalizeValue(
		bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null $value,
		Types\DataType $dataType,
		ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null $format,
	): bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null
	{
		if ($value === null) {
			return null;
		}

		if (
			$dataType->equalsValue(Types\DataType::CHAR)
			|| $dataType->equalsValue(Types\DataType::UCHAR)
			|| $dataType->equalsValue(Types\DataType::SHORT)
			|| $dataType->equalsValue(Types\DataType::USHORT)
			|| $dataType->equalsValue(Types\DataType::INT)
			|| $dataType->equalsValue(Types\DataType::UINT)
		) {
			$value = intval(self::flattenValue($value));

			if (
				$format instanceof ValueObjects\NumberRangeFormat
				&& (
					(
						$format->getMin() !== null
						&& intval($format->getMin()) > $value
					) || (
						$format->getMax() !== null
						&& intval($format->getMax()) < $value
					)
				)
			) {
				throw new Exceptions\InvalidValue(sprintf(
					'Provided value: "%d" is out of allowed value range: [%s, %s]',
					$value,
					strval(self::flattenValue($format->getMin())),
					strval(self::flattenValue($format->getMax())),
				));
			}

			return $value;
		} elseif ($dataType->equalsValue(Types\DataType::FLOAT)) {
			$value = floatval(self::flattenValue($value));

			if (
				$format instanceof ValueObjects\NumberRangeFormat
				&& (
					(
						$format->getMin() !== null
						&& floatval($format->getMin()) > $value
					) || (
						$format->getMax() !== null
						&& floatval($format->getMax()) < $value
					)
				)
			) {
				throw new Exceptions\InvalidValue(sprintf(
					'Provided value: "%f" is out of allowed value range: [%s, %s]',
					$value,
					strval(self::flattenValue($format->getMin())),
					strval(self::flattenValue($format->getMax())),
				));
			}

			return $value;
		} elseif ($dataType->equalsValue(Types\DataType::STRING)) {
			return strval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(Types\DataType::BOOLEAN)) {
			return in_array(
				Utils\Strings::lower(strval(self::flattenValue($value))),
				self::BOOL_TRUE_VALUES,
				true,
			);
		} elseif ($dataType->equalsValue(Types\DataType::DATE)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(Types\DataType::TIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(Types\DataType::DATETIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$formatted = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, strval(self::flattenValue($value)));

			if ($formatted === false) {
				$formatted = Utils\DateTime::createFromFormat(
					DateTimeInterface::RFC3339_EXTENDED,
					strval(self::flattenValue($value)),
				);
			}

			return $formatted === false ? null : $formatted;
		} elseif (
			$dataType->equalsValue(Types\DataType::BUTTON)
			|| $dataType->equalsValue(Types\DataType::SWITCH)
			|| $dataType->equalsValue(Types\DataType::COVER)
			|| $dataType->equalsValue(Types\DataType::ENUM)
		) {
			/** @var class-string<Button|Switcher|Cover>|null $payloadClass */
			$payloadClass = null;

			if ($dataType->equalsValue(Types\DataType::BUTTON)) {
				$payloadClass = Types\Payloads\Button::class;
			} elseif ($dataType->equalsValue(Types\DataType::SWITCH)) {
				$payloadClass = Types\Payloads\Switcher::class;
			} elseif ($dataType->equalsValue(Types\DataType::COVER)) {
				$payloadClass = Types\Payloads\Cover::class;
			}

			if ($format instanceof ValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					if (
						$payloadClass !== null
						&& (
							$dataType->equalsValue(Types\DataType::BUTTON)
							|| $dataType->equalsValue(Types\DataType::SWITCH)
							|| $dataType->equalsValue(Types\DataType::COVER)
						)
					) {
						return $payloadClass::isValidValue(self::flattenValue($value))
							? $payloadClass::get(self::flattenValue($value))
							: null;
					} else {
						return strval(self::flattenValue($value));
					}
				}

				throw new Exceptions\InvalidValue(
					sprintf(
						'Provided value: "%s" is not in valid rage: [%s]',
						strval(self::flattenValue($value)),
						implode(', ', $format->toArray()),
					),
				);
			} elseif ($format instanceof ValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static function (array $item) use ($value): bool {
						if ($item[0] === null) {
							return false;
						}

						return self::compareValues(
							$item[0]->getValue(),
							self::normalizeEnumItemValue($value, $item[0]->getDataType()),
						);
					},
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof ValueObjects\CombinedEnumFormatItem
				) {
					if (
						$payloadClass !== null
						&& (
							$dataType->equalsValue(Types\DataType::BUTTON)
							|| $dataType->equalsValue(Types\DataType::SWITCH)
							|| $dataType->equalsValue(Types\DataType::COVER)
						)
					) {
						return $payloadClass::isValidValue(self::flattenValue($filtered[0][0]->getValue()))
							? $payloadClass::get(self::flattenValue($filtered[0][0]->getValue()))
							: null;
					}

					return strval(self::flattenValue($filtered[0][0]->getValue()));
				}

				try {
					throw new Exceptions\InvalidValue(
						sprintf(
							'Provided value: "%s" is not in valid rage: [%s]',
							strval(self::flattenValue($value)),
							Utils\Json::encode($format->toArray()),
						),
					);
				} catch (Utils\JsonException $ex) {
					throw new Exceptions\InvalidValue(
						sprintf(
							'Provided value: "%s" is not in valid rage. Value format could not be converted to error',
							strval(self::flattenValue($value)),
						),
						$ex->getCode(),
						$ex,
					);
				}
			} else {
				if (
					$payloadClass !== null
					&& (
						$dataType->equalsValue(Types\DataType::BUTTON)
						|| $dataType->equalsValue(Types\DataType::SWITCH)
						|| $dataType->equalsValue(Types\DataType::COVER)
					)
				) {
					if ($payloadClass::isValidValue(self::flattenValue($value))) {
						return $payloadClass::get(self::flattenValue($value));
					}

					throw new Exceptions\InvalidValue(
						sprintf(
							'Provided value: "%s" is not in valid rage: [%s]',
							strval(self::flattenValue($value)),
							implode(', ', (array) $payloadClass::getAvailableValues()),
						),
					);
				}

				return strval(self::flattenValue($value));
			}
		}

		return $value;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public static function transformValueFromDevice(
		bool|int|float|string|null $value,
		Types\DataType $dataType,
		ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null $format,
	): bool|int|float|string|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(Types\DataType::BOOLEAN)) {
			return in_array(Utils\Strings::lower(strval($value)), self::BOOL_TRUE_VALUES, true);
		}

		if ($dataType->equalsValue(Types\DataType::FLOAT)) {
			return floatval($value);
		}

		if (
			$dataType->equalsValue(Types\DataType::UCHAR)
			|| $dataType->equalsValue(Types\DataType::CHAR)
			|| $dataType->equalsValue(Types\DataType::USHORT)
			|| $dataType->equalsValue(Types\DataType::SHORT)
			|| $dataType->equalsValue(Types\DataType::UINT)
			|| $dataType->equalsValue(Types\DataType::INT)
		) {
			return intval($value);
		}

		if ($dataType->equalsValue(Types\DataType::STRING)) {
			return strval($value);
		}

		if (
			$dataType->equalsValue(Types\DataType::BUTTON)
			|| $dataType->equalsValue(Types\DataType::SWITCH)
			|| $dataType->equalsValue(Types\DataType::COVER)
			|| $dataType->equalsValue(Types\DataType::ENUM)
		) {
			/** @var class-string<Button|Switcher|Cover>|null $payloadClass */
			$payloadClass = null;

			if ($dataType->equalsValue(Types\DataType::BUTTON)) {
				$payloadClass = Types\Payloads\Button::class;
			} elseif ($dataType->equalsValue(Types\DataType::SWITCH)) {
				$payloadClass = Types\Payloads\Switcher::class;
			} elseif ($dataType->equalsValue(Types\DataType::COVER)) {
				$payloadClass = Types\Payloads\Cover::class;
			}

			if ($format instanceof ValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					if (
						$payloadClass !== null
						&& (
							$dataType->equalsValue(Types\DataType::BUTTON)
							|| $dataType->equalsValue(Types\DataType::SWITCH)
							|| $dataType->equalsValue(Types\DataType::COVER)
						)
					) {
						return $payloadClass::isValidValue(self::flattenValue($value))
							? $payloadClass::get(self::flattenValue($value))
							: null;
					}

					return strval($value);
				}

				return null;
			} elseif ($format instanceof ValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static function (array $item) use ($value): bool {
						if ($item[1] === null) {
							return false;
						}

						return self::compareValues(
							$item[1]->getValue(),
							self::normalizeEnumItemValue($value, $item[1]->getDataType()),
						);
					},
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof ValueObjects\CombinedEnumFormatItem
				) {
					if (
						$payloadClass !== null
						&& (
							$dataType->equalsValue(Types\DataType::BUTTON)
							|| $dataType->equalsValue(Types\DataType::SWITCH)
							|| $dataType->equalsValue(Types\DataType::COVER)
						)
					) {
						return $payloadClass::isValidValue(self::flattenValue($filtered[0][0]->getValue()))
							? $payloadClass::get(self::flattenValue($filtered[0][0]->getValue()))
							: null;
					}

					return strval($filtered[0][0]->getValue());
				}

				return null;
			} else {
				if ($payloadClass !== null && $payloadClass::isValidValue(self::flattenValue($value))) {
					return $payloadClass::get(self::flattenValue($value));
				}
			}
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public static function transformValueToDevice(
		bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null $value,
		Types\DataType $dataType,
		ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null $format,
	): bool|int|float|string|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(Types\DataType::BOOLEAN)) {
			if (is_bool($value)) {
				return $value;
			}

			return null;
		}

		if ($dataType->equalsValue(Types\DataType::FLOAT)) {
			if (is_numeric($value)) {
				return floatval($value);
			}

			return null;
		}

		if (
			$dataType->equalsValue(Types\DataType::UCHAR)
			|| $dataType->equalsValue(Types\DataType::CHAR)
			|| $dataType->equalsValue(Types\DataType::USHORT)
			|| $dataType->equalsValue(Types\DataType::SHORT)
			|| $dataType->equalsValue(Types\DataType::UINT)
			|| $dataType->equalsValue(Types\DataType::INT)
		) {
			if (is_numeric($value)) {
				return intval($value);
			}

			return null;
		}

		if ($dataType->equalsValue(Types\DataType::STRING)) {
			if (is_string($value)) {
				return $value;
			}

			return null;
		}

		if ($dataType->equalsValue(Types\DataType::DATE)) {
			if ($value instanceof DateTime) {
				return $value->format(self::DATE_FORMAT);
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value->format(self::DATE_FORMAT);
		}

		if ($dataType->equalsValue(Types\DataType::TIME)) {
			if ($value instanceof DateTime) {
				return $value->format(self::TIME_FORMAT);
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value->format(self::TIME_FORMAT);
		}

		if ($dataType->equalsValue(Types\DataType::DATETIME)) {
			if ($value instanceof DateTime) {
				return $value->format(DateTimeInterface::ATOM);
			}

			$formatted = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, strval(self::flattenValue($value)));

			if ($formatted === false) {
				$formatted = Utils\DateTime::createFromFormat(
					DateTimeInterface::RFC3339_EXTENDED,
					strval(self::flattenValue($value)),
				);
			}

			return $formatted === false ? null : $formatted->format(DateTimeInterface::ATOM);
		}

		if (
			$dataType->equalsValue(Types\DataType::BUTTON)
			|| $dataType->equalsValue(Types\DataType::SWITCH)
			|| $dataType->equalsValue(Types\DataType::COVER)
			|| $dataType->equalsValue(Types\DataType::ENUM)
		) {
			/** @var class-string<Button|Switcher|Cover>|null $payloadClass */
			$payloadClass = null;

			if ($dataType->equalsValue(Types\DataType::BUTTON)) {
				$payloadClass = Types\Payloads\Button::class;
			} elseif ($dataType->equalsValue(Types\DataType::SWITCH)) {
				$payloadClass = Types\Payloads\Switcher::class;
			} elseif ($dataType->equalsValue(Types\DataType::COVER)) {
				$payloadClass = Types\Payloads\Cover::class;
			}

			if ($format instanceof ValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					return strval(self::flattenValue($value));
				}

				return null;
			} elseif ($format instanceof ValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static function (array $item) use ($value): bool {
						if ($item[0] === null) {
							return false;
						}

						return self::compareValues(
							$item[0]->getValue(),
							self::normalizeEnumItemValue($value, $item[0]->getDataType()),
						);
					},
				));

				if (
					count($filtered) === 1
					&& $filtered[0][2] instanceof ValueObjects\CombinedEnumFormatItem
				) {
					return self::flattenValue($filtered[0][2]->getValue());
				}

				return null;
			} else {
				if ($payloadClass !== null) {
					if ($value instanceof $payloadClass) {
						return strval($value->getValue());
					}

					return $payloadClass::isValidValue(self::flattenValue($value))
						? strval(self::flattenValue($value))
						: null;
				}
			}
		}

		return self::flattenValue($value);
	}

	public static function transformToScale(
		bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null $value,
		Types\DataType $dataType,
		int|null $scale = null,
	): bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null
	{
		if ($value === null) {
			return null;
		}

		if (
			in_array($dataType->getValue(), [
				Types\DataType::CHAR,
				Types\DataType::UCHAR,
				Types\DataType::SHORT,
				Types\DataType::USHORT,
				Types\DataType::INT,
				Types\DataType::UINT,
				Types\DataType::FLOAT,
			], true)
			&& (
				is_int($value)
				|| is_float($value)
			)
		) {
			if ($scale !== null) {
				$value = intval($value);

				for ($i = 0; $i < $scale; $i++) {
					$value /= 10;
				}

				$value = round(floatval($value), $scale);

				$value = $dataType->equalsValue(Types\DataType::FLOAT)
					? $value
					: intval($value);
			}
		}

		return $value;
	}

	public static function transformFromScale(
		bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null $value,
		Types\DataType $dataType,
		int|null $scale = null,
	): bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null
	{
		if ($value === null) {
			return null;
		}

		if (
			in_array($dataType->getValue(), [
				Types\DataType::CHAR,
				Types\DataType::UCHAR,
				Types\DataType::SHORT,
				Types\DataType::USHORT,
				Types\DataType::INT,
				Types\DataType::UINT,
				Types\DataType::FLOAT,
			], true)
			&& (
				is_int($value)
				|| is_float($value)
			)
		) {
			if ($scale !== null) {
				$value = floatval($value);

				for ($i = 0; $i < $scale; $i++) {
					$value *= 10;
				}

				$value = round(floatval($value));

				$value = $dataType->equalsValue(Types\DataType::FLOAT)
					? $value
					: intval($value);
			}
		}

		return $value;
	}

	public static function flattenValue(
		bool|int|float|string|DateTimeInterface|Consistence\Enum\Enum|null $value,
	): bool|int|float|string|null
	{
		if ($value instanceof DateTimeInterface) {
			return $value->format(DateTimeInterface::ATOM);
		} elseif ($value instanceof Consistence\Enum\Enum) {
			return is_numeric($value->getValue()) ? $value->getValue() : strval($value->getValue());
		}

		return $value;
	}

	public static function transformDataType(
		bool|int|float|string|null $value,
		Types\DataType $dataType,
	): bool|int|float|string|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(Types\DataType::BOOLEAN)) {
			return in_array(Utils\Strings::lower(strval($value)), self::BOOL_TRUE_VALUES, true);
		}

		if ($dataType->equalsValue(Types\DataType::FLOAT)) {
			return floatval($value);
		}

		if (
			$dataType->equalsValue(Types\DataType::UCHAR)
			|| $dataType->equalsValue(Types\DataType::CHAR)
			|| $dataType->equalsValue(Types\DataType::USHORT)
			|| $dataType->equalsValue(Types\DataType::SHORT)
			|| $dataType->equalsValue(Types\DataType::UINT)
			|| $dataType->equalsValue(Types\DataType::INT)
		) {
			return intval($value);
		}

		if ($dataType->equalsValue(Types\DataType::STRING)) {
			return strval($value);
		}

		return strval($value);
	}

	private static function normalizeEnumItemValue(
		bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null $value,
		Types\DataTypeShort|null $dataType,
	): bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null
	{
		if ($dataType === null) {
			return $value;
		}

		if (
			$dataType->equalsValue(Types\DataTypeShort::CHAR)
			|| $dataType->equalsValue(Types\DataTypeShort::UCHAR)
			|| $dataType->equalsValue(Types\DataTypeShort::SHORT)
			|| $dataType->equalsValue(Types\DataTypeShort::USHORT)
			|| $dataType->equalsValue(Types\DataTypeShort::INT)
			|| $dataType->equalsValue(Types\DataTypeShort::UINT)
		) {
			return intval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(Types\DataTypeShort::FLOAT)) {
			return floatval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(Types\DataTypeShort::STRING)) {
			return strval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(Types\DataTypeShort::BOOLEAN)) {
			return in_array(
				Utils\Strings::lower(strval(self::flattenValue($value))),
				self::BOOL_TRUE_VALUES,
				true,
			);
		} elseif ($dataType->equalsValue(Types\DataTypeShort::BUTTON)) {
			if ($value instanceof Types\Payloads\Button) {
				return $value;
			}

			return Types\Payloads\Button::isValidValue(self::flattenValue($value))
				? Types\Payloads\Button::get(self::flattenValue($value))
				: false;
		} elseif ($dataType->equalsValue(Types\DataTypeShort::SWITCH)) {
			if ($value instanceof Types\Payloads\Switcher) {
				return $value;
			}

			return Types\Payloads\Switcher::isValidValue(self::flattenValue($value))
				? Types\Payloads\Switcher::get(self::flattenValue($value))
				: false;
		} elseif ($dataType->equalsValue(Types\DataTypeShort::COVER)) {
			if ($value instanceof Types\Payloads\Cover) {
				return $value;
			}

			return Types\Payloads\Cover::isValidValue(self::flattenValue($value))
				? Types\Payloads\Cover::get(self::flattenValue($value))
				: false;
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATE)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(Types\DataTypeShort::TIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATETIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$formatted = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, strval(self::flattenValue($value)));

			if ($formatted === false) {
				$formatted = Utils\DateTime::createFromFormat(
					DateTimeInterface::RFC3339_EXTENDED,
					strval(self::flattenValue($value)),
				);
			}

			return $formatted === false ? null : $formatted;
		}

		return $value;
	}

	private static function compareValues(
		bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null $left,
		bool|int|float|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null $right,
	): bool
	{
		if ($left === $right) {
			return true;
		}

		$left = Utils\Strings::lower(strval(self::flattenValue($left)));
		$right = Utils\Strings::lower(strval(self::flattenValue($right)));

		return $left === $right;
	}

}
