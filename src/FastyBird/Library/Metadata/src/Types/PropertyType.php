<?php declare(strict_types = 1);

/**
 * PropertyType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           02.01.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Property type
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PropertyType extends Consistence\Enum\Enum
{

	/**
	 * Define states
	 */
	public const TYPE_VARIABLE = 'variable';

	public const TYPE_DYNAMIC = 'dynamic';

	public const TYPE_MAPPED = 'mapped';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
