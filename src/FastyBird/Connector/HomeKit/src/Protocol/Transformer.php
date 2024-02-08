<?php declare(strict_types = 1);

/**
 * Transformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           01.10.22
 */

namespace FastyBird\Connector\HomeKit\Protocol;

use DateTimeInterface;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Formats as MetadataFormats;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use Nette\Utils;
use function array_filter;
use function array_values;
use function count;
use function in_array;
use function intval;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_scalar;
use function max;
use function min;
use function preg_replace;
use function round;
use function str_replace;
use function strlen;
use function strval;
use function substr;

/**
 * Value transformers
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Transformer
{

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function fromClient(
		MetadataDocuments\DevicesModule\ChannelProperty|null $property,
		Types\DataType $dataType,
		bool|float|int|string|null $value,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		$transformedValue = null;

		// HAP transformation

		if ($dataType->equalsValue(Types\DataType::BOOLEAN)) {
			if ($value === null) {
				$transformedValue = false;
			} elseif (!is_bool($value)) {
				$transformedValue = in_array(Utils\Strings::lower(strval($value)), [
					'true',
					't',
					'yes',
					'y',
					'1',
					'on',
				], true);
			} else {
				$transformedValue = $value;
			}
		} elseif ($dataType->equalsValue(Types\DataType::FLOAT)) {
			if (is_float($value)) {
				$transformedValue = $value;
			} elseif (is_numeric($value)) {
				$transformedValue = (float) $value;
			} else {
				$transformedValue = str_replace([' ', ','], ['', '.'], (string) $value);

				if (!is_numeric($transformedValue)) {
					$transformedValue = 0.0;
				}

				$transformedValue = (float) $transformedValue;
			}
		} elseif (
			$dataType->equalsValue(Types\DataType::INT)
			|| $dataType->equalsValue(Types\DataType::UINT8)
			|| $dataType->equalsValue(Types\DataType::UINT16)
			|| $dataType->equalsValue(Types\DataType::UINT32)
			|| $dataType->equalsValue(Types\DataType::UINT64)
		) {
			if (is_int($value)) {
				$transformedValue = $value;
			} elseif (is_numeric($value) && strval($value) === strval((int) $value)) {
				$transformedValue = (int) $value;
			} else {
				$transformedValue = preg_replace('~\s~', '', (string) $value);
				$transformedValue = (int) $transformedValue;
			}
		} elseif ($dataType->equalsValue(Types\DataType::STRING)) {
			$transformedValue = strval($value);
		}

		// Connector transformation

		if ($transformedValue === null) {
			return null;
		}

		if ($property === null) {
			return $transformedValue;
		}

		if (
			$property->getDataType() === MetadataTypes\DataType::ENUM
			|| $property->getDataType() === MetadataTypes\DataType::SWITCH
			|| $property->getDataType() === MetadataTypes\DataType::COVER
			|| $property->getDataType() === MetadataTypes\DataType::BUTTON
		) {
			if ($property->getFormat() instanceof MetadataFormats\StringEnum) {
				$filtered = array_values(array_filter(
					$property->getFormat()->getItems(),
					static fn (string $item): bool => Utils\Strings::lower(strval($transformedValue)) === $item,
				));

				if (count($filtered) === 1) {
					if ($property->getDataType() === MetadataTypes\DataType::SWITCH) {
						return MetadataTypes\Payloads\Switcher::isValidValue(strval($transformedValue))
							? MetadataTypes\Payloads\Switcher::get(strval($transformedValue))
							: null;
					} elseif ($property->getDataType() === MetadataTypes\DataType::BUTTON) {
						return MetadataTypes\Payloads\Button::isValidValue(strval($transformedValue))
							? MetadataTypes\Payloads\Button::get(strval($transformedValue))
							: null;
					} elseif ($property->getDataType() === MetadataTypes\DataType::COVER) {
						return MetadataTypes\Payloads\Cover::isValidValue(strval($transformedValue))
							? MetadataTypes\Payloads\Cover::get(strval($transformedValue))
							: null;
					} else {
						return strval($transformedValue);
					}
				}

				return null;
			} elseif ($property->getFormat() instanceof MetadataFormats\CombinedEnum) {
				$filtered = array_values(array_filter(
					$property->getFormat()->getItems(),
					static fn (array $item): bool => $item[1] !== null
						&& Utils\Strings::lower(strval($item[1]->getValue())) === Utils\Strings::lower(
							strval($transformedValue),
						),
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof MetadataFormats\CombinedEnumItem
				) {
					if ($property->getDataType() === MetadataTypes\DataType::SWITCH) {
						return MetadataTypes\Payloads\Switcher::isValidValue(strval($filtered[0][0]->getValue()))
							? MetadataTypes\Payloads\Switcher::get(strval($filtered[0][0]->getValue()))
							: null;
					} elseif ($property->getDataType() === MetadataTypes\DataType::BUTTON) {
						return MetadataTypes\Payloads\Button::isValidValue(strval($filtered[0][0]->getValue()))
							? MetadataTypes\Payloads\Button::get(strval($filtered[0][0]->getValue()))
							: null;
					} elseif ($property->getDataType() === MetadataTypes\DataType::COVER) {
						return MetadataTypes\Payloads\Cover::isValidValue(strval($filtered[0][0]->getValue()))
							? MetadataTypes\Payloads\Cover::get(strval($filtered[0][0]->getValue()))
							: null;
					} else {
						return strval($filtered[0][0]->getValue());
					}
				}

				return null;
			}
		}

		return $transformedValue;
	}

	/**
	 * @param array<int>|null $validValues
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public static function toClient(
		MetadataDocuments\DevicesModule\ChannelProperty|null $property,
		Types\DataType $dataType,
		array|null $validValues,
		int|null $maxLength,
		float|null $minValue,
		float|null $maxValue,
		float|null $minStep,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
	): bool|float|int|string|null
	{
		$transformedValue = null;

		// Connector transformation

		if ($property !== null) {
			if (
				$property->getDataType() === MetadataTypes\DataType::ENUM
				|| $property->getDataType() === MetadataTypes\DataType::SWITCH
				|| $property->getDataType() === MetadataTypes\DataType::COVER
				|| $property->getDataType() === MetadataTypes\DataType::BUTTON
			) {
				if ($property->getFormat() instanceof MetadataFormats\StringEnum) {
					$filtered = array_values(array_filter(
						$property->getFormat()->getItems(),
						static fn (string $item): bool => Utils\Strings::lower(
							strval(MetadataUtilities\Value::flattenValue($value)),
						) === $item,
					));

					if (count($filtered) === 1) {
						$transformedValue = MetadataUtilities\Value::flattenValue($value);
					}
				} elseif ($property->getFormat() instanceof MetadataFormats\CombinedEnum) {
					$filtered = array_values(array_filter(
						$property->getFormat()->getItems(),
						static fn (array $item): bool => $item[0] !== null
							&& Utils\Strings::lower(strval($item[0]->getValue())) === Utils\Strings::lower(
								strval(MetadataUtilities\Value::flattenValue($value)),
							),
					));

					if (
						count($filtered) === 1
						&& $filtered[0][2] instanceof MetadataFormats\CombinedEnumItem
					) {
						$transformedValue = is_scalar($filtered[0][2]->getValue())
							? $filtered[0][2]->getValue()
							: MetadataUtilities\Value::flattenValue($filtered[0][2]->getValue());
					}
				} else {
					if (
						(
							$property->getDataType() === MetadataTypes\DataType::SWITCH
							&& $value instanceof MetadataTypes\Payloads\Switcher
						) || (
							$property->getDataType() === MetadataTypes\DataType::BUTTON
							&& $value instanceof MetadataTypes\Payloads\Button
						) || (
							$property->getDataType() === MetadataTypes\DataType::COVER
							&& $value instanceof MetadataTypes\Payloads\Cover
						)
					) {
						$transformedValue = strval($value->getValue());
					}
				}
			} else {
				$transformedValue = $value;
			}
		} else {
			$transformedValue = $value;
		}

		// HAP transformation

		if ($dataType->equalsValue(Types\DataType::BOOLEAN)) {
			if ($transformedValue === null) {
				$transformedValue = false;
			} elseif (!is_bool($transformedValue)) {
				$transformedValue = in_array(
					Utils\Strings::lower(strval(MetadataUtilities\Value::flattenValue($transformedValue))),
					[
						'true',
						't',
						'yes',
						'y',
						'1',
						'on',
					],
					true,
				);
			}
		} elseif ($dataType->equalsValue(Types\DataType::FLOAT)) {
			if (!is_numeric($transformedValue)) {
				$transformedValue = str_replace(
					[' ', ','],
					['', '.'],
					strval(MetadataUtilities\Value::flattenValue($transformedValue)),
				);

				if (!is_numeric($transformedValue)) {
					$transformedValue = 0.0;
				}
			}

			$transformedValue = (float) $transformedValue;

			if ($minStep !== null) {
				$transformedValue = round($minStep * round($transformedValue / $minStep), 14);
			}

			$transformedValue = min($maxValue ?? $transformedValue, $transformedValue);
			$transformedValue = max($minValue ?? $transformedValue, $transformedValue);
		} elseif (
			$dataType->equalsValue(Types\DataType::INT)
			|| $dataType->equalsValue(Types\DataType::UINT8)
			|| $dataType->equalsValue(Types\DataType::UINT16)
			|| $dataType->equalsValue(Types\DataType::UINT32)
			|| $dataType->equalsValue(Types\DataType::UINT64)
		) {
			if (is_bool($transformedValue)) {
				$transformedValue = $transformedValue ? 1 : 0;
			}

			if (!is_numeric($transformedValue) || strval($transformedValue) !== strval((int) $transformedValue)) {
				$transformedValue = preg_replace(
					'~\s~',
					'',
					strval(MetadataUtilities\Value::flattenValue($transformedValue)),
				);
			}

			$transformedValue = (int) $transformedValue;

			if ($minStep !== null) {
				$transformedValue = round($minStep * round($transformedValue / $minStep), 14);
			}

			$transformedValue = (int) min($maxValue ?? $transformedValue, $transformedValue);
			$transformedValue = (int) max($minValue ?? $transformedValue, $transformedValue);
		} elseif ($dataType->equalsValue(Types\DataType::STRING)) {
			$transformedValue = $value !== null ? substr(
				strval(MetadataUtilities\Value::flattenValue($value)),
				0,
				($maxLength ?? strlen(strval(MetadataUtilities\Value::flattenValue($value)))),
			) : '';
		}

		if (
			$validValues !== null
			&& !in_array(intval(MetadataUtilities\Value::flattenValue($transformedValue)), $validValues, true)
		) {
			$transformedValue = null;
		}

		return MetadataUtilities\Value::flattenValue($transformedValue);
	}

}
