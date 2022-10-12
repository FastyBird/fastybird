<?php declare(strict_types = 1);

/**
 * PropertyType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.14.0
 *
 * @date           02.01.22
 */

namespace FastyBird\Metadata\Types;

use Consistence;
use function strval;

/**
 * Property type
 *
 * @package        FastyBird:Metadata!
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
