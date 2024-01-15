<?php declare(strict_types = 1);

/**
 * ValueHelper.php
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
use Contributte\Monolog;
use DateTime;
use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\ValueObjects;
use MathSolver;
use Nette\Utils;
use function array_filter;
use function array_values;
use function boolval;
use function count;
use function floatval;
use function implode;
use function in_array;
use function intval;
use function is_array;
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
final class ValueHelper
{

	private const DATE_FORMAT = 'Y-m-d';

	private const TIME_FORMAT = 'H:i:sP';

	private const BOOL_TRUE_VALUES = ['true', 't', 'yes', 'y', '1', 'on'];

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\InvalidValue
	 */
	public static function normalizeValue(
		Types\DataType $dataType,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null $format = null,
	): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			$dataType->equalsValue(Types\DataType::DATA_TYPE_CHAR)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_UCHAR)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SHORT)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_USHORT)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_INT)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_UINT)
		) {
			if ($format instanceof ValueObjects\NumberRangeFormat) {
				if ($format->getMin() !== null && intval($format->getMin()) > intval(self::flattenValue($value))) {
					throw new Exceptions\InvalidValue(sprintf(
						'Provided value: %s is out of allowed value range: %s, %s',
						strval(self::flattenValue($value)),
						strval(self::flattenValue($format->getMin())),
						strval(self::flattenValue($format->getMax())),
					));
				}

				if ($format->getMax() !== null && intval($format->getMax()) < intval(self::flattenValue($value))) {
					throw new Exceptions\InvalidValue(sprintf(
						'Provided value: %s is out of allowed value range: %s, %s',
						strval(self::flattenValue($value)),
						strval(self::flattenValue($format->getMin())),
						strval(self::flattenValue($format->getMax())),
					));
				}
			}

			return intval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_FLOAT)) {
			if ($format instanceof ValueObjects\NumberRangeFormat) {
				if ($format->getMin() !== null && floatval($format->getMin()) > floatval(self::flattenValue($value))) {
					throw new Exceptions\InvalidValue(sprintf(
						'Provided value: %s is out of allowed value range: %s, %s',
						strval(self::flattenValue($value)),
						strval(self::flattenValue($format->getMin())),
						strval(self::flattenValue($format->getMax())),
					));
				}

				if ($format->getMax() !== null && floatval($format->getMax()) < floatval(self::flattenValue($value))) {
					throw new Exceptions\InvalidValue(sprintf(
						'Provided value: %s is out of allowed value range: %s, %s',
						strval(self::flattenValue($value)),
						strval(self::flattenValue($format->getMin())),
						strval(self::flattenValue($format->getMax())),
					));
				}
			}

			return floatval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_STRING)) {
			return strval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_BOOLEAN)) {
			return in_array(
				Utils\Strings::lower(strval(self::flattenValue($value))),
				self::BOOL_TRUE_VALUES,
				true,
			);
		} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_DATE)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_TIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_DATETIME)) {
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
			$dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_ENUM)
		) {
			/** @var class-string<Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)) {
				$payloadClass = Types\ButtonPayload::class;
			} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)) {
				$payloadClass = Types\SwitchPayload::class;
			} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)) {
				$payloadClass = Types\CoverPayload::class;
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
							$dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)
							|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)
							|| $dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)
						)
					) {
						return $payloadClass::isValidValue(self::flattenValue($value))
							? $payloadClass::get(self::flattenValue($value))
							: null;
					} else {
						return strval(self::flattenValue($value));
					}
				}

				throw new Exceptions\InvalidArgument(
					sprintf(
						'Provided value "%s" is not in valid rage: %s',
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
							self::normalizeEnumItemValue($item[0]->getDataType(), $value),
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
							$dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)
							|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)
							|| $dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)
						)
					) {
						return $payloadClass::isValidValue(self::flattenValue($filtered[0][0]->getValue()))
							? $payloadClass::get(self::flattenValue($filtered[0][0]->getValue()))
							: null;
					}

					return strval(self::flattenValue($filtered[0][0]->getValue()));
				}

				try {
					throw new Exceptions\InvalidArgument(
						sprintf(
							'Provided value "%s" is not in valid rage: %s',
							strval(self::flattenValue($value)),
							Utils\Json::encode($format->toArray()),
						),
					);
				} catch (Utils\JsonException $ex) {
					throw new Exceptions\InvalidArgument(
						sprintf(
							'Provided value "%s" is not in valid rage. Value format could not be converted to error',
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
						$dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)
						|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)
						|| $dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)
					)
				) {
					if ($payloadClass::isValidValue(self::flattenValue($value))) {
						return $payloadClass::get(self::flattenValue($value));
					}

					throw new Exceptions\InvalidArgument(
						sprintf(
							'Provided value "%s" is not in valid rage: %s',
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

	public static function transformReadValue(
		Types\DataType $dataType,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
		ValueObjects\EquationTransformer|null $transformer = null,
		int|null $scale = null,
	): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			in_array($dataType->getValue(), [
				Types\DataType::DATA_TYPE_CHAR,
				Types\DataType::DATA_TYPE_UCHAR,
				Types\DataType::DATA_TYPE_SHORT,
				Types\DataType::DATA_TYPE_USHORT,
				Types\DataType::DATA_TYPE_INT,
				Types\DataType::DATA_TYPE_UINT,
				Types\DataType::DATA_TYPE_FLOAT,
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
			}

			if ($transformer instanceof ValueObjects\EquationTransformer) {
				$value = @$transformer->getEquationFrom()->substitute(['y' => $value])->simplify()->string();
				$value = is_array($value) ? implode('', $value) : $value;
				$value = @MathSolver\Math::from('calc[' . $value . ']')->simplify()->string();

				$value = $dataType->equalsValue(Types\DataType::DATA_TYPE_FLOAT)
					? floatval($value)
					: intval(round(floatval($value)));
			}
		}

		return $value;
	}

	public static function transformWriteValue(
		Types\DataType $dataType,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
		ValueObjects\EquationTransformer|null $transformer = null,
		int|null $scale = null,
	): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			in_array($dataType->getValue(), [
				Types\DataType::DATA_TYPE_CHAR,
				Types\DataType::DATA_TYPE_UCHAR,
				Types\DataType::DATA_TYPE_SHORT,
				Types\DataType::DATA_TYPE_USHORT,
				Types\DataType::DATA_TYPE_INT,
				Types\DataType::DATA_TYPE_UINT,
				Types\DataType::DATA_TYPE_FLOAT,
			], true)
			&& (
				is_int($value)
				|| is_float($value)
			)
		) {
			if (
				$transformer instanceof ValueObjects\EquationTransformer
				&& $transformer->getEquationTo() !== null
			) {
				$value = @$transformer->getEquationTo()->substitute(['x' => $value])->simplify()->string();
				$value = is_array($value) ? implode('', $value) : $value;
				$value = @MathSolver\Math::from('calc[' . $value . ']')->simplify()->string();

				$value = $dataType->equalsValue(Types\DataType::DATA_TYPE_FLOAT)
					? floatval($value)
					: intval(round(floatval($value)));
			}

			if ($scale !== null) {
				$value = floatval($value);

				for ($i = 0; $i < $scale; $i++) {
					$value *= 10;
				}

				$value = round(floatval($value));

				$value = $dataType->equalsValue(Types\DataType::DATA_TYPE_FLOAT)
					? $value
					: intval($value);
			}
		}

		return $value;
	}

	public static function flattenValue(
		bool|float|int|string|DateTimeInterface|Consistence\Enum\Enum|null $value,
	): bool|float|int|string|null
	{
		if ($value instanceof DateTimeInterface) {
			return $value->format(DateTimeInterface::ATOM);
		} elseif ($value instanceof Consistence\Enum\Enum) {
			return is_numeric($value->getValue()) ? $value->getValue() : strval($value->getValue());
		}

		return $value;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public static function transformValueFromDevice(
		Types\DataType $dataType,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null $format,
		string|int|float|bool|null $value,
	): float|int|string|bool|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_BOOLEAN)) {
			return in_array(Utils\Strings::lower(strval($value)), self::BOOL_TRUE_VALUES, true);
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_FLOAT)) {
			return floatval($value);
		}

		if (
			$dataType->equalsValue(Types\DataType::DATA_TYPE_UCHAR)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_CHAR)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_USHORT)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SHORT)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_UINT)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_INT)
		) {
			return intval($value);
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_STRING)) {
			return strval($value);
		}

		if (
			$dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_ENUM)
		) {
			/** @var class-string<Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)) {
				$payloadClass = Types\ButtonPayload::class;
			} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)) {
				$payloadClass = Types\SwitchPayload::class;
			} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)) {
				$payloadClass = Types\CoverPayload::class;
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
							$dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)
							|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)
							|| $dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)
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
							self::normalizeEnumItemValue($item[1]->getDataType(), $value),
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
							$dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)
							|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)
							|| $dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)
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
		Types\DataType $dataType,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null $format,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
	): string|int|float|bool|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_BOOLEAN)) {
			if (is_bool($value)) {
				return $value;
			}

			return null;
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_FLOAT)) {
			if (is_numeric($value)) {
				return floatval($value);
			}

			return null;
		}

		if (
			$dataType->equalsValue(Types\DataType::DATA_TYPE_UCHAR)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_CHAR)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_USHORT)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SHORT)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_UINT)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_INT)
		) {
			if (is_numeric($value)) {
				return intval($value);
			}

			return null;
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_STRING)) {
			if (is_string($value)) {
				return $value;
			}

			return null;
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_DATE)) {
			if ($value instanceof DateTime) {
				return $value->format(self::DATE_FORMAT);
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value->format(self::DATE_FORMAT);
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_TIME)) {
			if ($value instanceof DateTime) {
				return $value->format(self::TIME_FORMAT);
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value->format(self::TIME_FORMAT);
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_DATETIME)) {
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
			$dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)
			|| $dataType->equalsValue(Types\DataType::DATA_TYPE_ENUM)
		) {
			/** @var class-string<Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)) {
				$payloadClass = Types\ButtonPayload::class;
			} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)) {
				$payloadClass = Types\SwitchPayload::class;
			} elseif ($dataType->equalsValue(Types\DataType::DATA_TYPE_COVER)) {
				$payloadClass = Types\CoverPayload::class;
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
							self::normalizeEnumItemValue($item[0]->getDataType(), $value),
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

	public static function transformValueFromMappedParent(
		Types\DataType $dataType,
		Types\DataType $parentDataType,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		if ($dataType->equalsValue($parentDataType->getValue())) {
			return $value;
		}

		if (
			in_array(
				$dataType->getValue(),
				[
					Types\DataType::DATA_TYPE_CHAR,
					Types\DataType::DATA_TYPE_UCHAR,
					Types\DataType::DATA_TYPE_SHORT,
					Types\DataType::DATA_TYPE_USHORT,
					Types\DataType::DATA_TYPE_INT,
					Types\DataType::DATA_TYPE_UINT,
					Types\DataType::DATA_TYPE_FLOAT,
				],
				true,
			)
			&& in_array(
				$parentDataType->getValue(),
				[
					Types\DataType::DATA_TYPE_CHAR,
					Types\DataType::DATA_TYPE_UCHAR,
					Types\DataType::DATA_TYPE_SHORT,
					Types\DataType::DATA_TYPE_USHORT,
					Types\DataType::DATA_TYPE_INT,
					Types\DataType::DATA_TYPE_UINT,
					Types\DataType::DATA_TYPE_FLOAT,
				],
				true,
			)
		) {
			return $value;
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_BOOLEAN)) {
			if (
				$parentDataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)
				&& (
					$value instanceof Types\SwitchPayload
					|| $value === null
				)
			) {
				return $value?->equalsValue(Types\SwitchPayload::PAYLOAD_ON) ?? false;
			} elseif (
				$parentDataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)
				&& (
					$value instanceof Types\ButtonPayload
					|| $value === null
				)
			) {
				return $value?->equalsValue(Types\ButtonPayload::PAYLOAD_PRESSED) ?? false;
			}
		}

		Monolog\LoggerHolder::getInstance()->getLogger()->warning(
			'Parent property value could not be transformed to mapped property value',
			[
				'source' => Types\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'value-helper',
				'mapped_data_type' => $dataType->getValue(),
				'parent_data_type' => $parentDataType->getValue(),
				'value' => self::flattenValue($value),
			],
		);

		return $value;
	}

	public static function transformValueToMappedParent(
		Types\DataType $dataType,
		Types\DataType $parentDataType,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		if ($dataType->equalsValue($parentDataType->getValue())) {
			return $value;
		}

		if (
			in_array(
				$dataType->getValue(),
				[
					Types\DataType::DATA_TYPE_CHAR,
					Types\DataType::DATA_TYPE_UCHAR,
					Types\DataType::DATA_TYPE_SHORT,
					Types\DataType::DATA_TYPE_USHORT,
					Types\DataType::DATA_TYPE_INT,
					Types\DataType::DATA_TYPE_UINT,
					Types\DataType::DATA_TYPE_FLOAT,
				],
				true,
			)
			&& in_array(
				$parentDataType->getValue(),
				[
					Types\DataType::DATA_TYPE_CHAR,
					Types\DataType::DATA_TYPE_UCHAR,
					Types\DataType::DATA_TYPE_SHORT,
					Types\DataType::DATA_TYPE_USHORT,
					Types\DataType::DATA_TYPE_INT,
					Types\DataType::DATA_TYPE_UINT,
					Types\DataType::DATA_TYPE_FLOAT,
				],
				true,
			)
		) {
			return $value;
		}

		if ($dataType->equalsValue(Types\DataType::DATA_TYPE_BOOLEAN)) {
			if ($parentDataType->equalsValue(Types\DataType::DATA_TYPE_SWITCH)) {
				return Types\SwitchPayload::get(
					boolval($value)
						? Types\SwitchPayload::PAYLOAD_ON
						: Types\SwitchPayload::PAYLOAD_OFF,
				);
			} elseif ($parentDataType->equalsValue(Types\DataType::DATA_TYPE_BUTTON)) {
				return Types\ButtonPayload::get(
					boolval($value)
						? Types\ButtonPayload::PAYLOAD_PRESSED
						: Types\ButtonPayload::PAYLOAD_RELEASED,
				);
			}
		}

		Monolog\LoggerHolder::getInstance()->getLogger()->warning(
			'Mapped property value could not be transformed to parent property value',
			[
				'source' => Types\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'value-helper',
				'mapped_data_type' => $dataType->getValue(),
				'parent_data_type' => $parentDataType->getValue(),
				'value' => self::flattenValue($value),
			],
		);

		return $value;
	}

	private static function normalizeEnumItemValue(
		Types\DataTypeShort|null $dataType,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null
	{
		if ($dataType === null) {
			return $value;
		}

		if (
			$dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_CHAR)
			|| $dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_UCHAR)
			|| $dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_SHORT)
			|| $dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_USHORT)
			|| $dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_INT)
			|| $dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_UINT)
		) {
			return intval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_FLOAT)) {
			return floatval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_STRING)) {
			return strval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_BOOLEAN)) {
			return in_array(
				Utils\Strings::lower(strval(self::flattenValue($value))),
				self::BOOL_TRUE_VALUES,
				true,
			);
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_BUTTON)) {
			if ($value instanceof Types\ButtonPayload) {
				return $value;
			}

			return Types\ButtonPayload::isValidValue(self::flattenValue($value))
				? Types\ButtonPayload::get(self::flattenValue($value))
				: false;
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_SWITCH)) {
			if ($value instanceof Types\SwitchPayload) {
				return $value;
			}

			return Types\SwitchPayload::isValidValue(self::flattenValue($value))
				? Types\SwitchPayload::get(self::flattenValue($value))
				: false;
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_COVER)) {
			if ($value instanceof Types\CoverPayload) {
				return $value;
			}

			return Types\CoverPayload::isValidValue(self::flattenValue($value))
				? Types\CoverPayload::get(self::flattenValue($value))
				: false;
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_DATE)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_TIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(Types\DataTypeShort::DATA_TYPE_DATETIME)) {
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
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $left,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $right,
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
