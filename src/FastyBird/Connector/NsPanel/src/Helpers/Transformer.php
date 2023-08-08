<?php declare(strict_types = 1);

/**
 * Transformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           16.07.23
 */

namespace FastyBird\Connector\NsPanel\Helpers;

use DateTimeInterface;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function array_filter;
use function array_values;
use function boolval;
use function count;
use function floatval;
use function intval;
use function is_bool;
use function strval;

/**
 * States values data transformers
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Transformer
{

	use Nette\SmartObject;

	/**
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function transformValueFromDevice(
		MetadataTypes\DataType $dataType,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		int|float|string|bool|Types\MotorCalibrationPayload|Types\MotorControlPayload|Types\PowerPayload|Types\PressPayload|Types\StartupPayload|Types\TogglePayload|null $value,
	): float|int|string|bool|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null
	{
		if ($value === null) {
			return null;
		}

		$value = DevicesUtilities\ValueHelper::flattenValue($value);

		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_STRING)) {
			return self::valueToString($value);
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			return is_bool($value) ? $value : boolval($value);
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

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::valueToString($value) === $item,
				));

				if (count($filtered) === 1) {
					return MetadataTypes\SwitchPayload::isValidValue(self::valueToString($value))
						? MetadataTypes\SwitchPayload::get(self::valueToString($value))
						: null;
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[1] !== null
						&& self::valueToString($item[1]->getValue()) === self::valueToString($value),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return MetadataTypes\SwitchPayload::isValidValue(strval($filtered[0][0]->getValue()))
						? MetadataTypes\SwitchPayload::get(strval($filtered[0][0]->getValue()))
						: null;
				}

				return null;
			}
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::valueToString($value) === $item,
				));

				if (count($filtered) === 1) {
					return MetadataTypes\ButtonPayload::isValidValue(self::valueToString($value))
						? MetadataTypes\ButtonPayload::get(self::valueToString($value))
						: null;
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[1] !== null
						&& self::valueToString($item[1]->getValue()) === self::valueToString($value),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return MetadataTypes\ButtonPayload::isValidValue(strval($filtered[0][0]->getValue()))
						? MetadataTypes\ButtonPayload::get(strval($filtered[0][0]->getValue()))
						: null;
				}

				return null;
			}
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::valueToString($value) === $item,
				));

				if (count($filtered) === 1) {
					return self::valueToString($value);
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[1] !== null
						&& self::valueToString($item[1]->getValue()) === self::valueToString($value),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return strval($filtered[0][0]->getValue());
				}

				return null;
			}
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_DATETIME)) {
			$value = Utils\DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, self::valueToString($value));

			return $value === false ? null : $value;
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

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::valueToString($value) === $item,
				));

				if (count($filtered) === 1) {
					return self::valueToString($value);
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[0] !== null
						&& self::valueToString($item[0]->getValue()) === self::valueToString($value),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][2] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return strval($filtered[0][2]->getValue());
				}

				return null;
			}
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::valueToString($value) === $item,
				));

				if (count($filtered) === 1) {
					return self::valueToString($value);
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[0] !== null
						&& self::valueToString($item[0]->getValue()) === self::valueToString($value),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][2] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return strval($filtered[0][2]->getValue());
				}

				return null;
			}
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::valueToString($value) === $item,
				));

				if (count($filtered) === 1) {
					return self::valueToString($value);
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[0] !== null
						&& self::valueToString($item[0]->getValue()) === self::valueToString($value),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][2] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return strval($filtered[0][2]->getValue());
				}

				return null;
			}
		}

		return DevicesUtilities\ValueHelper::flattenValue($value);
	}

	private static function valueToString(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload $value,
	): string
	{
		$value = DevicesUtilities\ValueHelper::flattenValue($value);

		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}

		return Utils\Strings::lower(strval($value));
	}

}
