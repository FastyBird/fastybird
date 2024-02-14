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

use BackedEnum;
use Consistence\Enum\Enum;
use DateTime;
use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Formats;
use FastyBird\Library\Metadata\Types;
use Nette\Utils;
use function array_filter;
use function array_map;
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
		bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null $value,
		Types\DataType $dataType,
		Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null $format,
	): bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			$dataType === Types\DataType::CHAR
			|| $dataType === Types\DataType::UCHAR
			|| $dataType === Types\DataType::SHORT
			|| $dataType === Types\DataType::USHORT
			|| $dataType === Types\DataType::INT
			|| $dataType === Types\DataType::UINT
		) {
			$value = intval(self::flattenValue($value));

			if (
				$format instanceof Formats\NumberRange
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
		} elseif ($dataType === Types\DataType::FLOAT) {
			$value = floatval(self::flattenValue($value));

			if (
				$format instanceof Formats\NumberRange
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
		} elseif ($dataType === Types\DataType::STRING) {
			return strval(self::flattenValue($value));
		} elseif ($dataType === Types\DataType::BOOLEAN) {
			return in_array(
				Utils\Strings::lower(strval(self::flattenValue($value))),
				self::BOOL_TRUE_VALUES,
				true,
			);
		} elseif ($dataType === Types\DataType::DATE) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType === Types\DataType::TIME) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType === Types\DataType::DATETIME) {
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
			$dataType === Types\DataType::BUTTON
			|| $dataType === Types\DataType::SWITCH
			|| $dataType === Types\DataType::COVER
			|| $dataType === Types\DataType::ENUM
		) {
			/** @var class-string<Types\Payloads\Payload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType === Types\DataType::BUTTON) {
				$payloadClass = Types\Payloads\Button::class;
			} elseif ($dataType === Types\DataType::SWITCH) {
				$payloadClass = Types\Payloads\Switcher::class;
			} elseif ($dataType === Types\DataType::COVER) {
				$payloadClass = Types\Payloads\Cover::class;
			}

			if ($format instanceof Formats\StringEnum) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					return $payloadClass !== null
						&& (
							$dataType === Types\DataType::BUTTON
							|| $dataType === Types\DataType::SWITCH
							|| $dataType === Types\DataType::COVER
						)
					 ? $payloadClass::tryFrom(strval(self::flattenValue($value))) : strval(self::flattenValue($value));
				}

				throw new Exceptions\InvalidValue(
					sprintf(
						'Provided value: "%s" is not in valid rage: [%s]',
						strval(self::flattenValue($value)),
						implode(', ', $format->toArray()),
					),
				);
			} elseif ($format instanceof Formats\CombinedEnum) {
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
					&& $filtered[0][0] instanceof Formats\CombinedEnumItem
				) {
					if (
						$payloadClass !== null
						&& (
							$dataType === Types\DataType::BUTTON
							|| $dataType === Types\DataType::SWITCH
							|| $dataType === Types\DataType::COVER
						)
					) {
						return $payloadClass::tryFrom(strval(self::flattenValue($filtered[0][0]->getValue())));
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
						$dataType === Types\DataType::BUTTON
						|| $dataType === Types\DataType::SWITCH
						|| $dataType === Types\DataType::COVER
					)
				) {
					if ($payloadClass::tryFrom(strval(self::flattenValue($value))) !== null) {
						return $payloadClass::tryFrom(strval(self::flattenValue($value)));
					}

					throw new Exceptions\InvalidValue(
						sprintf(
							'Provided value: "%s" is not in valid rage: [%s]',
							strval(self::flattenValue($value)),
							implode(
								', ',
								array_map(
									static fn (Types\Payloads\Payload $enum): string => strval($enum->value),
									$payloadClass::cases(),
								),
							),
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
		Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null $format,
	): bool|int|float|string|Types\Payloads\Payload|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType === Types\DataType::BOOLEAN) {
			return in_array(Utils\Strings::lower(strval($value)), self::BOOL_TRUE_VALUES, true);
		}

		if ($dataType === Types\DataType::FLOAT) {
			return floatval($value);
		}

		if (
			$dataType === Types\DataType::UCHAR
			|| $dataType === Types\DataType::CHAR
			|| $dataType === Types\DataType::USHORT
			|| $dataType === Types\DataType::SHORT
			|| $dataType === Types\DataType::UINT
			|| $dataType === Types\DataType::INT
		) {
			return intval($value);
		}

		if ($dataType === Types\DataType::STRING) {
			return strval($value);
		}

		if (
			$dataType === Types\DataType::BUTTON
			|| $dataType === Types\DataType::SWITCH
			|| $dataType === Types\DataType::COVER
			|| $dataType === Types\DataType::ENUM
		) {
			/** @var class-string<Types\Payloads\Payload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType === Types\DataType::BUTTON) {
				$payloadClass = Types\Payloads\Button::class;
			} elseif ($dataType === Types\DataType::SWITCH) {
				$payloadClass = Types\Payloads\Switcher::class;
			} elseif ($dataType === Types\DataType::COVER) {
				$payloadClass = Types\Payloads\Cover::class;
			}

			if ($format instanceof Formats\StringEnum) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					if (
						$payloadClass !== null
						&& (
							$dataType === Types\DataType::BUTTON
							|| $dataType === Types\DataType::SWITCH
							|| $dataType === Types\DataType::COVER
						)
					) {
						return $payloadClass::tryFrom(strval(self::flattenValue($value)));
					}

					return strval($value);
				}

				return null;
			} elseif ($format instanceof Formats\CombinedEnum) {
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
					&& $filtered[0][0] instanceof Formats\CombinedEnumItem
				) {
					if (
						$payloadClass !== null
						&& (
							$dataType === Types\DataType::BUTTON
							|| $dataType === Types\DataType::SWITCH
							|| $dataType === Types\DataType::COVER
						)
					) {
						return $payloadClass::tryFrom(strval(self::flattenValue($filtered[0][0]->getValue())));
					}

					return strval(self::flattenValue($filtered[0][0]->getValue()));
				}

				return null;
			} else {
				if ($payloadClass !== null && $payloadClass::tryFrom(strval(self::flattenValue($value))) !== null) {
					return $payloadClass::tryFrom(strval(self::flattenValue($value)));
				}
			}
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public static function transformValueToDevice(
		bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null $value,
		Types\DataType $dataType,
		Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null $format,
	): bool|int|float|string|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType === Types\DataType::BOOLEAN) {
			if (is_bool($value)) {
				return $value;
			}

			return null;
		}

		if ($dataType === Types\DataType::FLOAT) {
			if (is_numeric($value)) {
				return floatval($value);
			}

			return null;
		}

		if (
			$dataType === Types\DataType::UCHAR
			|| $dataType === Types\DataType::CHAR
			|| $dataType === Types\DataType::USHORT
			|| $dataType === Types\DataType::SHORT
			|| $dataType === Types\DataType::UINT
			|| $dataType === Types\DataType::INT
		) {
			if (is_numeric($value)) {
				return intval($value);
			}

			return null;
		}

		if ($dataType === Types\DataType::STRING) {
			if (is_string($value)) {
				return $value;
			}

			return null;
		}

		if ($dataType === Types\DataType::DATE) {
			if ($value instanceof DateTime) {
				return $value->format(self::DATE_FORMAT);
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value->format(self::DATE_FORMAT);
		}

		if ($dataType === Types\DataType::TIME) {
			if ($value instanceof DateTime) {
				return $value->format(self::TIME_FORMAT);
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value->format(self::TIME_FORMAT);
		}

		if ($dataType === Types\DataType::DATETIME) {
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
			$dataType === Types\DataType::BUTTON
			|| $dataType === Types\DataType::SWITCH
			|| $dataType === Types\DataType::COVER
			|| $dataType === Types\DataType::ENUM
		) {
			/** @var class-string<Types\Payloads\Payload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType === Types\DataType::BUTTON) {
				$payloadClass = Types\Payloads\Button::class;
			} elseif ($dataType === Types\DataType::SWITCH) {
				$payloadClass = Types\Payloads\Switcher::class;
			} elseif ($dataType === Types\DataType::COVER) {
				$payloadClass = Types\Payloads\Cover::class;
			}

			if ($format instanceof Formats\StringEnum) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					return strval(self::flattenValue($value));
				}

				return null;
			} elseif ($format instanceof Formats\CombinedEnum) {
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
					&& $filtered[0][2] instanceof Formats\CombinedEnumItem
				) {
					return self::flattenValue($filtered[0][2]->getValue());
				}

				return null;
			} else {
				if ($payloadClass !== null) {
					if ($value instanceof $payloadClass) {
						return $value->value;
					}

					return $payloadClass::tryFrom(strval(self::flattenValue($value))) !== null
						? strval(self::flattenValue($value))
						: null;
				}
			}
		}

		return self::flattenValue($value);
	}

	public static function transformToScale(
		bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null $value,
		Types\DataType $dataType,
		int|null $scale = null,
	): bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			in_array(
				$dataType,
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

				$value = $dataType === Types\DataType::FLOAT
					? $value
					: intval($value);
			}
		}

		return $value;
	}

	public static function transformFromScale(
		bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null $value,
		Types\DataType $dataType,
		int|null $scale = null,
	): bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			in_array(
				$dataType,
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

				$value = $dataType === Types\DataType::FLOAT
					? $value
					: intval($value);
			}
		}

		return $value;
	}

	public static function flattenValue(
		bool|int|float|string|DateTimeInterface|BackedEnum|Enum|null $value,
	): bool|int|float|string|null
	{
		if ($value instanceof DateTimeInterface) {
			return $value->format(DateTimeInterface::ATOM);
		} elseif ($value instanceof BackedEnum) {
			return $value->value;
		} elseif ($value instanceof Enum) {
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

		if ($dataType === Types\DataType::BOOLEAN) {
			return in_array(Utils\Strings::lower(strval($value)), self::BOOL_TRUE_VALUES, true);
		}

		if ($dataType === Types\DataType::FLOAT) {
			return floatval($value);
		}

		if (
			$dataType === Types\DataType::UCHAR
			|| $dataType === Types\DataType::CHAR
			|| $dataType === Types\DataType::USHORT
			|| $dataType === Types\DataType::SHORT
			|| $dataType === Types\DataType::UINT
			|| $dataType === Types\DataType::INT
		) {
			return intval($value);
		}

		if ($dataType === Types\DataType::STRING) {
			return strval($value);
		}

		return strval($value);
	}

	private static function normalizeEnumItemValue(
		bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null $value,
		Types\DataTypeShort|null $dataType,
	): bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null
	{
		if ($dataType === null) {
			return $value;
		}

		if (
			$dataType === Types\DataTypeShort::CHAR
			|| $dataType === Types\DataTypeShort::UCHAR
			|| $dataType === Types\DataTypeShort::SHORT
			|| $dataType === Types\DataTypeShort::USHORT
			|| $dataType === Types\DataTypeShort::INT
			|| $dataType === Types\DataTypeShort::UINT
		) {
			return intval(self::flattenValue($value));
		} elseif ($dataType === Types\DataTypeShort::FLOAT) {
			return floatval(self::flattenValue($value));
		} elseif ($dataType === Types\DataTypeShort::STRING) {
			return strval(self::flattenValue($value));
		} elseif ($dataType === Types\DataTypeShort::BOOLEAN) {
			return in_array(
				Utils\Strings::lower(strval(self::flattenValue($value))),
				self::BOOL_TRUE_VALUES,
				true,
			);
		} elseif ($dataType === Types\DataTypeShort::BUTTON) {
			if ($value instanceof Types\Payloads\Button) {
				return $value;
			}

			return Types\Payloads\Button::tryFrom(strval(self::flattenValue($value))) ?? false;
		} elseif ($dataType === Types\DataTypeShort::SWITCH) {
			if ($value instanceof Types\Payloads\Switcher) {
				return $value;
			}

			return Types\Payloads\Switcher::tryFrom(strval(self::flattenValue($value))) ?? false;
		} elseif ($dataType === Types\DataTypeShort::COVER) {
			if ($value instanceof Types\Payloads\Cover) {
				return $value;
			}

			return Types\Payloads\Cover::tryFrom(strval(self::flattenValue($value))) ?? false;
		} elseif ($dataType === Types\DataTypeShort::DATE) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType === Types\DataTypeShort::TIME) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType === Types\DataTypeShort::DATETIME) {
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
		bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null $left,
		bool|int|float|string|DateTimeInterface|Types\Payloads\Payload|null $right,
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
