<?php declare(strict_types = 1);

/**
 * DataType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
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
	public const CHAR = 'char';

	public const UCHAR = 'uchar';

	public const SHORT = 'short';

	public const USHORT = 'ushort';

	public const INT = 'int';

	public const UINT = 'uint';

	public const FLOAT = 'float';

	public const BOOLEAN = 'bool';

	public const STRING = 'string';

	public const ENUM = 'enum';

	public const DATE = 'date';

	public const TIME = 'time';

	public const DATETIME = 'datetime';

	public const COLOR = 'color';

	public const BUTTON = 'button';

	public const SWITCH = 'switch';

	public const COVER = 'cover';

	public const UNKNOWN = 'unknown';

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
