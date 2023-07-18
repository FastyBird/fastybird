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
use function count;
use function floatval;
use function implode;
use function in_array;
use function intval;
use function is_float;
use function is_int;
use function is_numeric;
use function round;
use function sprintf;
use function strval;
use const DATE_ATOM;

/**
 * Value helpers
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ValueHelper
{

	/**
	 * @throws Exceptions\InvalidState
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
			return $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			return in_array(
				Utils\Strings::lower(strval(self::flattenValue($value))),
				['true', 't', 'yes', 'y', '1', 'on'],
				true,
			);
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_DATE)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat('Y-m-d', strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_TIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat('H:i:sP', strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_DATETIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, strval(self::flattenValue($value)));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
			if ($value instanceof MetadataTypes\ButtonPayload) {
				return $value;
			}

			if (MetadataTypes\ButtonPayload::isValidValue(strval(self::flattenValue($value)))) {
				return MetadataTypes\ButtonPayload::get(strval(self::flattenValue($value)));
			}

			throw new Exceptions\InvalidState(
				sprintf(
					'Provided value "%s" is not in valid rage: %s',
					strval(self::flattenValue($value)),
					implode(', ', (array) MetadataTypes\ButtonPayload::getAvailableValues()),
				),
			);
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
			if ($value instanceof MetadataTypes\SwitchPayload) {
				return $value;
			}

			if (MetadataTypes\SwitchPayload::isValidValue(strval(self::flattenValue($value)))) {
				return MetadataTypes\SwitchPayload::get(strval(self::flattenValue($value)));
			}

			throw new Exceptions\InvalidState(
				sprintf(
					'Provided value "%s" is not in valid rage: %s',
					strval(self::flattenValue($value)),
					implode(', ', (array) MetadataTypes\SwitchPayload::getAvailableValues()),
				),
			);
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)) {
			if ($value instanceof MetadataTypes\CoverPayload) {
				return $value;
			}

			if (MetadataTypes\CoverPayload::isValidValue(strval(self::flattenValue($value)))) {
				return MetadataTypes\CoverPayload::get(strval(self::flattenValue($value)));
			}

			throw new Exceptions\InvalidState(
				sprintf(
					'Provided value "%s" is not in valid rage: %s',
					strval(self::flattenValue($value)),
					implode(', ', (array) MetadataTypes\CoverPayload::getAvailableValues()),
				),
			);
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_filter(
					$format->getItems(),
					static fn (string $item): bool => Utils\Strings::lower(
						strval(self::flattenValue($value)),
					) === Utils\Strings::lower(
						strval($item),
					)
				);

				if (count($filtered) === 1) {
					return $value;
				}

				throw new Exceptions\InvalidState(
					sprintf(
						'Provided value "%s" is not in valid rage: %s',
						strval(self::flattenValue($value)),
						strval($format),
					),
				);
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_filter(
					$format->getItems(),
					static function (array $item) use ($value): bool {
						$filteredInner = array_filter(
							$item,
							static function (MetadataValueObjects\CombinedEnumFormatItem|null $part) use ($value): bool {
								if ($part === null) {
									return false;
								}

								return Utils\Strings::lower(
									strval(self::flattenValue($value)),
								) === Utils\Strings::lower(
									strval(self::flattenValue($part->getValue())),
								);
							},
						);

						return count($filteredInner) === 1;
					},
				);

				if (count($filtered) === 1) {
					return $value;
				}

				throw new Exceptions\InvalidState(
					sprintf(
						'Provided value "%s" is not in valid rage: %s',
						strval(self::flattenValue($value)),
						strval($format),
					),
				);
			}

			return null;
		}

		return $value;
	}

	/**
	 * @throws Exceptions\InvalidState
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
	 * @throws Exceptions\InvalidState
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
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|null
	{
		if ($value instanceof DateTimeInterface) {
			return $value->format(DATE_ATOM);
		} elseif ($value instanceof Consistence\Enum\Enum) {
			return is_numeric($value->getValue()) ? $value->getValue() : strval($value->getValue());
		}

		return $value;
	}

}
