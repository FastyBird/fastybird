<?php declare(strict_types = 1);

/**
 * DataType.php
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

use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\ValueObjects;
use function floatval;
use function intval;

/**
 * Data type helpers
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DataType
{

	private const RANGES = [
		Types\DataType::CHAR => [-128, 127],
		Types\DataType::UCHAR => [0, 255],
		Types\DataType::SHORT => [-32_768, 32_767],
		Types\DataType::USHORT => [0, 65_535],
		Types\DataType::INT => [-2_147_483_648, 2_147_483_647],
		Types\DataType::UINT => [0, 4_294_967_295],
	];

	/**
	 * @throws Exceptions\InvalidState
	 */
	public static function determineNumberDataType(
		ValueObjects\NumberRangeFormat $format,
		float|int|null $step = null,
		Types\DataType|null $fallback = null,
	): Types\DataType
	{
		if (
			$format->getMinDataType() !== null
			|| $format->getMaxDataType() !== null
		) {
			return match ($format->getMinDataType()?->getValue() ?? $format->getMaxDataType()?->getValue()) {
				Types\DataTypeShort::CHAR => Types\DataType::get(Types\DataType::CHAR),
				Types\DataTypeShort::UCHAR => Types\DataType::get(Types\DataType::UCHAR),
				Types\DataTypeShort::SHORT => Types\DataType::get(Types\DataType::SHORT),
				Types\DataTypeShort::USHORT => Types\DataType::get(Types\DataType::USHORT),
				Types\DataTypeShort::INT => Types\DataType::get(Types\DataType::INT),
				Types\DataTypeShort::UINT => Types\DataType::get(Types\DataType::UINT),
				Types\DataTypeShort::FLOAT => Types\DataType::get(Types\DataType::FLOAT),
				default => Types\DataType::get(Types\DataType::UNKNOWN),
			};
		}

		if (
			$step !== null
			// If step is defined and is float number, data type have to be float
			&& floatval(intval($step)) !== $step
		) {
			Types\DataType::get(Types\DataType::FLOAT);
		}

		if (
			(
				$format->getMin() !== null
				// If minimum value is defined and is float number, data type have to be float
				&& floatval(intval($format->getMin())) !== $format->getMin()
			) || (
				$format->getMax() !== null
				// If maximum value is defined and is float number, data type have to be float
				&& floatval(intval($format->getMax())) !== $format->getMax()
			)
		) {
			Types\DataType::get(Types\DataType::FLOAT);
		}

		if ($format->getMin() !== null || $format->getMax() !== null) {
			foreach (self::RANGES as $dataType => $ranges) {
				if (
					(
						$format->getMin() === null
						|| (
							$format->getMin() >= $ranges[0]
							&& $format->getMin() <= $ranges[1]
						)
					) && (
						$format->getMax() === null
						|| (
							$format->getMax() >= $ranges[0]
							&& $format->getMax() <= $ranges[1]
						)
					)
				) {
					return Types\DataType::get($dataType);
				}
			}

			Types\DataType::get(Types\DataType::FLOAT);
		}

		return $fallback ?? Types\DataType::get(Types\DataType::UNKNOWN);
	}

}
