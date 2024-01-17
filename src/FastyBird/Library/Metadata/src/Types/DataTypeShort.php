<?php declare(strict_types = 1);

/**
 * DataTypeShort.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           04.08.22
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
class DataTypeShort extends Consistence\Enum\Enum
{

	/**
	 * Define data types
	 */
	public const CHAR = 'i8';

	public const UCHAR = 'u8';

	public const SHORT = 'i16';

	public const USHORT = 'u16';

	public const INT = 'i32';

	public const UINT = 'u32';

	public const FLOAT = 'f';

	public const BOOLEAN = 'b';

	public const STRING = 's';

	public const ENUM = 'e';

	public const DATE = 'd';

	public const TIME = 't';

	public const DATETIME = 'dt';

	public const BUTTON = 'btn';

	public const SWITCH = 'sw';

	public const COVER = 'cvr';

	public const UNKNOWN = 'unk';

	public function isInteger(): bool
	{
		return self::equalsValue(self::CHAR)
			|| self::equalsValue(self::UCHAR)
			|| self::equalsValue(self::SHORT)
			|| self::equalsValue(self::USHORT)
			|| self::equalsValue(self::INT)
			|| self::equalsValue(self::UINT);
	}

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
