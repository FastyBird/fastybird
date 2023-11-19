<?php declare(strict_types = 1);

/**
 * ValueHelper.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           05.12.20
 */

namespace FastyBird\Module\Devices\Utilities;

use Consistence;
use DateTime;
use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Exceptions;
use Nette\Utils;
use function array_filter;
use function array_values;
use function boolval;
use function count;
use function floatval;
use function implode;
use function in_array;
use function intval;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function round;
use function sprintf;
use function strval;

/**
 * Value helpers
 *
 * @package        FastyBird:DevicesModule!
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
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function normalizeValue(
		MetadataTypes\DataType $dataType,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format = null,
		float|int|string|null $invalid = null,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
		) {
			if ($invalid !== null && intval($invalid) === intval(self::flattenValue($value))) {
				return $invalid;
			}

			if ($format instanceof MetadataValueObjects\NumberRangeFormat) {
				if ($format->getMin() !== null && intval($format->getMin()) > intval(self::flattenValue($value))) {
					return intval($format->getMin());
				}

				if ($format->getMax() !== null && intval($format->getMax()) < intval(self::flattenValue($value))) {
					return intval($format->getMax());
				}
			}

			return intval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)) {
			if ($invalid !== null && floatval($invalid) === floatval(self::flattenValue($value))) {
				return $invalid;
			}

			if ($format instanceof MetadataValueObjects\NumberRangeFormat) {
				if ($format->getMin() !== null && floatval($format->getMin()) > floatval(self::flattenValue($value))) {
					return floatval($format->getMin());
				}

				if ($format->getMax() !== null && floatval($format->getMax()) < floatval(self::flattenValue($value))) {
					return floatval($format->getMax());
				}
			}

			return floatval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_STRING)) {
			return strval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			return in_array(
				Utils\Strings::lower(strval(self::flattenValue($value))),
				self::BOOL_TRUE_VALUES,
				true,
			);
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_DATE)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_TIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_DATETIME)) {
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
			$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
		) {
			/** @var class-string<MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
				$payloadClass = MetadataTypes\ButtonPayload::class;
			} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
				$payloadClass = MetadataTypes\SwitchPayload::class;
			} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)) {
				$payloadClass = MetadataTypes\CoverPayload::class;
			}

			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					if (
						$payloadClass !== null
						&& (
							$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
							|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
							|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)
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
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
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
					&& $filtered[0][0] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					if (
						$payloadClass !== null
						&& (
							$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
							|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
							|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)
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
						$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
						|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
						|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)
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

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function normalizeReadValue(
		MetadataTypes\DataType $dataType,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format = null,
		int|null $scale,
		float|int|string|null $invalid = null,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if ($value === null) {
			return null;
		}

		$value = self::normalizeValue($dataType, $value, $format, $invalid);

		if (
			in_array($dataType->getValue(), [
				MetadataTypes\DataType::DATA_TYPE_CHAR,
				MetadataTypes\DataType::DATA_TYPE_UCHAR,
				MetadataTypes\DataType::DATA_TYPE_SHORT,
				MetadataTypes\DataType::DATA_TYPE_USHORT,
				MetadataTypes\DataType::DATA_TYPE_INT,
				MetadataTypes\DataType::DATA_TYPE_UINT,
				MetadataTypes\DataType::DATA_TYPE_FLOAT,
			], true)
			&& (
				is_int($value)
				|| is_float($value)
			)
		) {
			if ($format instanceof MetadataValueObjects\EquationFormat) {
				$value = $format->getEquationFrom()->substitute(['y' => $value])->simplify()->string();

				$value = $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)
					? floatval($value)
					: intval($value);
			}

			if ($scale !== null) {
				$value = intval($value);

				for ($i = 0; $i < $scale; $i++) {
					$value /= 10;
				}

				$value = round(floatval($value), $scale);
			}
		}

		return $value;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function normalizeWriteValue(
		MetadataTypes\DataType $dataType,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format = null,
		int|null $scale,
		float|int|string|null $invalid = null,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			in_array($dataType->getValue(), [
				MetadataTypes\DataType::DATA_TYPE_CHAR,
				MetadataTypes\DataType::DATA_TYPE_UCHAR,
				MetadataTypes\DataType::DATA_TYPE_SHORT,
				MetadataTypes\DataType::DATA_TYPE_USHORT,
				MetadataTypes\DataType::DATA_TYPE_INT,
				MetadataTypes\DataType::DATA_TYPE_UINT,
				MetadataTypes\DataType::DATA_TYPE_FLOAT,
			], true)
			&& (
				is_int($value)
				|| is_float($value)
			)
		) {
			if ($format instanceof MetadataValueObjects\EquationFormat && $format->getEquationTo() !== null) {
				$value = $format->getEquationTo()->substitute(['x' => $value])->simplify()->string();

				$value = $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)
					? floatval($value)
					: intval($value);
			}

			if ($scale !== null) {
				$value = floatval($value);

				for ($i = 0; $i < $scale; $i++) {
					$value *= 10;
				}

				$value = intval($value);
			}
		}

		return self::normalizeValue($dataType, $value, $format, $invalid);
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
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function transformValueFromDevice(
		MetadataTypes\DataType $dataType,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format,
		string|int|float|bool|null $value,
	): float|int|string|bool|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			return in_array(Utils\Strings::lower(strval($value)), self::BOOL_TRUE_VALUES, true);
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)) {
			$floatValue = floatval($value);

			if ($format instanceof MetadataValueObjects\NumberRangeFormat) {
				if ($format->getMin() !== null && $format->getMin() > $floatValue) {
					return null;
				}

				if ($format->getMax() !== null && $format->getMax() < $floatValue) {
					return null;
				}
			}

			return $floatValue;
		}

		if (
			$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
		) {
			$intValue = intval($value);

			if ($format instanceof MetadataValueObjects\NumberRangeFormat) {
				if ($format->getMin() !== null && $format->getMin() > $intValue) {
					return null;
				}

				if ($format->getMax() !== null && $format->getMax() < $intValue) {
					return null;
				}
			}

			return $intValue;
		}

		if (
			$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
		) {
			/** @var class-string<MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
				$payloadClass = MetadataTypes\ButtonPayload::class;
			} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
				$payloadClass = MetadataTypes\SwitchPayload::class;
			} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)) {
				$payloadClass = MetadataTypes\CoverPayload::class;
			}

			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					if (
						$payloadClass !== null
						&& (
							$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
							|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
							|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)
						)
					) {
						return $payloadClass::isValidValue(self::flattenValue($value))
							? $payloadClass::get(self::flattenValue($value))
							: null;
					}

					return strval($value);
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
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
					&& $filtered[0][0] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					if (
						$payloadClass !== null
						&& (
							$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
							|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
							|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)
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
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function transformValueToDevice(
		MetadataTypes\DataType $dataType,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): string|int|float|bool|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			if (is_bool($value)) {
				return $value;
			}

			return null;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_DATE)) {
			if ($value instanceof DateTime) {
				return $value->format(self::DATE_FORMAT);
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value->format(self::DATE_FORMAT);
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_TIME)) {
			if ($value instanceof DateTime) {
				return $value->format(self::TIME_FORMAT);
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value->format(self::TIME_FORMAT);
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_DATETIME)) {
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
			$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
		) {
			/** @var class-string<MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
				$payloadClass = MetadataTypes\ButtonPayload::class;
			} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
				$payloadClass = MetadataTypes\SwitchPayload::class;
			} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)) {
				$payloadClass = MetadataTypes\CoverPayload::class;
			}

			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					return strval(self::flattenValue($value));
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
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
					&& $filtered[0][2] instanceof MetadataValueObjects\CombinedEnumFormatItem
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

	/**
	 * @throws Exceptions\InvalidState
	 */
	public static function transformValueFromMappedParent(
		MetadataTypes\DataType $dataType,
		MetadataTypes\DataType $parentDataType,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if ($dataType->equals($parentDataType)) {
			return $value;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			if (
				$parentDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
				&& (
					$value instanceof MetadataTypes\SwitchPayload
					|| $value === null
				)
			) {
				return $value?->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_ON) ?? false;
			} elseif (
				$parentDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
				&& (
					$value instanceof MetadataTypes\ButtonPayload
					|| $value === null
				)
			) {
				return $value?->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_PRESSED) ?? false;
			}
		}

		throw new Exceptions\InvalidState('Parent property value could not be transformed to mapped property value');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public static function transformValueToMappedParent(
		MetadataTypes\DataType $dataType,
		MetadataTypes\DataType $parentDataType,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if ($dataType->equals($parentDataType)) {
			return $value;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			if ($parentDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
				return MetadataTypes\SwitchPayload::get(
					boolval($value)
						? MetadataTypes\SwitchPayload::PAYLOAD_ON
						: MetadataTypes\SwitchPayload::PAYLOAD_OFF,
				);
			} elseif ($parentDataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
				return MetadataTypes\ButtonPayload::get(
					boolval($value)
						? MetadataTypes\ButtonPayload::PAYLOAD_PRESSED
						: MetadataTypes\ButtonPayload::PAYLOAD_RELEASED,
				);
			}
		}

		throw new Exceptions\InvalidState('Mapped property value could not be transformed to parent property value');
	}

	private static function normalizeEnumItemValue(
		MetadataTypes\DataTypeShort|null $dataType,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if ($dataType === null) {
			return $value;
		}

		if (
			$dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_CHAR)
			|| $dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR)
			|| $dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_SHORT)
			|| $dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_USHORT)
			|| $dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_INT)
			|| $dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_UINT)
		) {
			return intval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_FLOAT)) {
			return floatval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_STRING)) {
			return strval(self::flattenValue($value));
		} elseif ($dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_BOOLEAN)) {
			return in_array(
				Utils\Strings::lower(strval(self::flattenValue($value))),
				self::BOOL_TRUE_VALUES,
				true,
			);
		} elseif ($dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_BUTTON)) {
			if ($value instanceof MetadataTypes\ButtonPayload) {
				return $value;
			}

			return MetadataTypes\ButtonPayload::isValidValue(self::flattenValue($value))
				? MetadataTypes\ButtonPayload::get(self::flattenValue($value))
				: false;
		} elseif ($dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH)) {
			if ($value instanceof MetadataTypes\SwitchPayload) {
				return $value;
			}

			return MetadataTypes\SwitchPayload::isValidValue(self::flattenValue($value))
				? MetadataTypes\SwitchPayload::get(self::flattenValue($value))
				: false;
		} elseif ($dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_COVER)) {
			if ($value instanceof MetadataTypes\CoverPayload) {
				return $value;
			}

			return MetadataTypes\CoverPayload::isValidValue(self::flattenValue($value))
				? MetadataTypes\CoverPayload::get(self::flattenValue($value))
				: false;
		} elseif ($dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_DATE)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_TIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataTypeShort::DATA_TYPE_DATETIME)) {
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
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $left,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $right,
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
