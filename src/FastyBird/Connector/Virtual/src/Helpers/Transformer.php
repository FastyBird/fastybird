<?php declare(strict_types = 1);

/**
 * Transformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           21.10.23
 */

namespace FastyBird\Connector\Virtual\Helpers;

use DateTimeInterface;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
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
 * Value transformers
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Transformer
{

	/**
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function normalizeValue(
		MetadataTypes\DataType $dataType,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			return is_bool($value) ? $value : boolval(DevicesUtilities\ValueHelper::flattenValue($value));
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)) {
			$floatValue = floatval(DevicesUtilities\ValueHelper::flattenValue($value));

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
			$intValue = intval(DevicesUtilities\ValueHelper::flattenValue($value));

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
					static fn (string $item): bool => Utils\Strings::lower(
						strval(DevicesUtilities\ValueHelper::flattenValue($value)),
					) === $item,
				));

				if (count($filtered) === 1) {
					return MetadataTypes\SwitchPayload::isValidValue(
						strval(DevicesUtilities\ValueHelper::flattenValue($value)),
					)
						? MetadataTypes\SwitchPayload::get(strval(DevicesUtilities\ValueHelper::flattenValue($value)))
						: null;
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[1] !== null
						&& Utils\Strings::lower(strval($item[1]->getValue()))
							=== Utils\Strings::lower(strval(DevicesUtilities\ValueHelper::flattenValue($value))),
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

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_COVER)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => Utils\Strings::lower(
						strval(DevicesUtilities\ValueHelper::flattenValue($value)),
					) === $item,
				));

				if (count($filtered) === 1) {
					return MetadataTypes\CoverPayload::isValidValue(
						strval(DevicesUtilities\ValueHelper::flattenValue($value)),
					)
						? MetadataTypes\CoverPayload::get(strval(DevicesUtilities\ValueHelper::flattenValue($value)))
						: null;
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[1] !== null
						&& Utils\Strings::lower(strval($item[1]->getValue()))
							=== Utils\Strings::lower(strval(DevicesUtilities\ValueHelper::flattenValue($value))),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof MetadataValueObjects\CombinedEnumFormatItem
				) {
					return MetadataTypes\CoverPayload::isValidValue(strval($filtered[0][0]->getValue()))
						? MetadataTypes\CoverPayload::get(strval($filtered[0][0]->getValue()))
						: null;
				}

				return null;
			}
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)) {
			if ($format instanceof MetadataValueObjects\StringEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => Utils\Strings::lower(
						strval(DevicesUtilities\ValueHelper::flattenValue($value)),
					) === $item,
				));

				if (count($filtered) === 1) {
					return strval(DevicesUtilities\ValueHelper::flattenValue($value));
				}

				return null;
			} elseif ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (array $item): bool => $item[1] !== null
						&& Utils\Strings::lower(strval($item[1]->getValue()))
							=== Utils\Strings::lower(strval(DevicesUtilities\ValueHelper::flattenValue($value))),
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

		return null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 */
	public static function fromMappedParent(
		DevicesEntities\Channels\Properties\Mapped $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		$parent = $property->getParent();

		if (!$parent instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return $value;
		}

		if ($property->getDataType()->equals($parent->getDataType())) {
			return $value;
		}

		if ($property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			if (
				$parent->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
				&& (
					$value instanceof MetadataTypes\SwitchPayload
					|| $value === null
				)
			) {
				return $value?->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_ON) ?? false;
			} elseif (
				$parent->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
				&& (
					$value instanceof MetadataTypes\ButtonPayload
					|| $value === null
				)
			) {
				return $value?->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_PRESSED) ?? false;
			}
		}

		throw new Exceptions\InvalidState(
			'Value received from mapped property could not be transformed into virtual device',
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 */
	public static function toMappedParent(
		DevicesEntities\Channels\Properties\Mapped $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		$parent = $property->getParent();

		if (!$parent instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return $value;
		}

		if ($property->getDataType()->equals($parent->getDataType())) {
			return $value;
		}

		if ($property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			if ($parent->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
				return MetadataTypes\SwitchPayload::get(
					boolval($value)
						? MetadataTypes\SwitchPayload::PAYLOAD_ON
						: MetadataTypes\SwitchPayload::PAYLOAD_OFF,
				);
			} elseif ($parent->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
				return MetadataTypes\ButtonPayload::get(
					boolval($value)
						? MetadataTypes\ButtonPayload::PAYLOAD_PRESSED
						: MetadataTypes\ButtonPayload::PAYLOAD_RELEASED,
				);
			}
		}

		throw new Exceptions\InvalidState(
			'Value received from virtual device could not be transformed into mapped property',
		);
	}

}
