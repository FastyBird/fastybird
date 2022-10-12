<?php declare(strict_types = 1);

/**
 * ValueHelper.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          0.51.0
 *
 * @date           05.12.20
 */

namespace FastyBird\DevicesModule\Utilities;

use Consistence;
use DateTime;
use DateTimeInterface;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\Metadata\ValueObjects as MetadataValueObjects;
use Nette\Utils;
use function array_filter;
use function count;
use function floatval;
use function in_array;
use function intval;
use function is_numeric;
use function strval;
use const DATE_ATOM;

/**
 * Value helpersphpstan-param
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ValueHelper
{

	public static function normalizeValue(
		MetadataTypes\DataType $dataType,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|null $value,
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|null $format = null,
		float|int|string|null $invalid = null,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|null
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
			if ($invalid === intval($value)) {
				return $invalid;
			}

			if ($format instanceof MetadataValueObjects\NumberRangeFormat) {
				if ($format->getMin() !== null && intval($format->getMin()) > intval($value)) {
					return null;
				}

				if ($format->getMax() !== null && intval($format->getMax()) < intval($value)) {
					return null;
				}
			}

			return intval($value);
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)) {
			if ($invalid === floatval($value)) {
				return $invalid;
			}

			if ($format instanceof MetadataValueObjects\NumberRangeFormat) {
				if ($format->getMin() !== null && intval($format->getMin()) > intval($value)) {
					return null;
				}

				if ($format->getMax() !== null && intval($format->getMax()) < intval($value)) {
					return null;
				}
			}

			return floatval($value);
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_STRING)) {
			return $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			return in_array(Utils\Strings::lower(strval($value)), ['true', 't', 'yes', 'y', '1', 'on'], true);
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_DATE)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat('Y-m-d', strval($value));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_TIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat('H:i:sP', strval($value));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_DATETIME)) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, strval($value));

			return $value === false ? null : $value;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
			if ($value instanceof MetadataTypes\ButtonPayload) {
				return $value;
			}

			if (MetadataTypes\ButtonPayload::isValidValue(strval($value))) {
				return MetadataTypes\ButtonPayload::get($value);
			}

			return null;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
			if ($value instanceof MetadataTypes\SwitchPayload) {
				return $value;
			}

			if (MetadataTypes\SwitchPayload::isValidValue(strval($value))) {
				return MetadataTypes\SwitchPayload::get($value);
			}

			return null;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_filter(
					$format->getItems(),
					static fn (string $item): bool => Utils\Strings::lower(strval($value)) === $item
				);

				if (count($filtered) === 1) {
					return $value;
				}

				return null;
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

								return Utils\Strings::lower(strval($value)) === $part->getValue();
							},
						);

						return count($filteredInner) === 1;
					},
				);

				if (count($filtered) === 1) {
					return $value;
				}

				return null;
			}

			return null;
		}

		return $value;
	}

	public static function flattenValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|null $value,
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
