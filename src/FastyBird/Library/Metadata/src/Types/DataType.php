<?php declare(strict_types = 1);

/**
 * DataType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          0.1.0
 *
 * @date           24.09.18
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Device or channel property data types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DataType extends Consistence\Enum\Enum
{

	/**
	 * Define data types
	 */
	public const DATA_TYPE_CHAR = 'char';

	public const DATA_TYPE_UCHAR = 'uchar';

	public const DATA_TYPE_SHORT = 'short';

	public const DATA_TYPE_USHORT = 'ushort';

	public const DATA_TYPE_INT = 'int';

	public const DATA_TYPE_UINT = 'uint';

	public const DATA_TYPE_FLOAT = 'float';

	public const DATA_TYPE_BOOLEAN = 'bool';

	public const DATA_TYPE_STRING = 'string';

	public const DATA_TYPE_ENUM = 'enum';

	public const DATA_TYPE_DATE = 'date';

	public const DATA_TYPE_TIME = 'time';

	public const DATA_TYPE_DATETIME = 'datetime';

	public const DATA_TYPE_COLOR = 'color';

	public const DATA_TYPE_BUTTON = 'button';

	public const DATA_TYPE_SWITCH = 'switch';

	public const DATA_TYPE_COVER = 'cover';

	public const DATA_TYPE_UNKNOWN = 'unknown';

	public function isInteger(): bool
	{
		return self::equalsValue(self::DATA_TYPE_CHAR)
			|| self::equalsValue(self::DATA_TYPE_UCHAR)
			|| self::equalsValue(self::DATA_TYPE_SHORT)
			|| self::equalsValue(self::DATA_TYPE_USHORT)
			|| self::equalsValue(self::DATA_TYPE_INT)
			|| self::equalsValue(self::DATA_TYPE_UINT);
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
