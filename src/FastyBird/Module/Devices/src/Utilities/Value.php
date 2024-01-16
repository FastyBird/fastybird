<?php declare(strict_types = 1);

/**
 * Value.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           16.01.24
 */

namespace FastyBird\Module\Devices\Utilities;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use function in_array;

/**
 * Useful value helpers
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Value
{

	public static function compareDataTypes(
		MetadataTypes\DataType $left,
		MetadataTypes\DataType $right,
	): bool
	{
		if ($left->equalsValue($right->getValue())) {
			return true;
		}

		return in_array(
			$left->getValue(),
			[
				MetadataTypes\DataType::DATA_TYPE_CHAR,
				MetadataTypes\DataType::DATA_TYPE_UCHAR,
				MetadataTypes\DataType::DATA_TYPE_SHORT,
				MetadataTypes\DataType::DATA_TYPE_USHORT,
				MetadataTypes\DataType::DATA_TYPE_INT,
				MetadataTypes\DataType::DATA_TYPE_UINT,
				MetadataTypes\DataType::DATA_TYPE_FLOAT,
			],
			true,
		)
			&& in_array(
				$right->getValue(),
				[
					MetadataTypes\DataType::DATA_TYPE_CHAR,
					MetadataTypes\DataType::DATA_TYPE_UCHAR,
					MetadataTypes\DataType::DATA_TYPE_SHORT,
					MetadataTypes\DataType::DATA_TYPE_USHORT,
					MetadataTypes\DataType::DATA_TYPE_INT,
					MetadataTypes\DataType::DATA_TYPE_UINT,
					MetadataTypes\DataType::DATA_TYPE_FLOAT,
				],
				true,
			);
	}

}
