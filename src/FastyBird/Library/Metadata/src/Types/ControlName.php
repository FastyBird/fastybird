<?php declare(strict_types = 1);

/**
 * ControlName.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 * @since          0.7.0
 *
 * @date           29.09.21
 */

namespace FastyBird\Metadata\Types;

use Consistence;
use function strval;

/**
 * Control name types
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ControlName extends Consistence\Enum\Enum
{

	/**
	 * Define controls names
	 */
	public const NAME_CONFIGURE = 'configure';

	public const NAME_RESET = 'reset';

	public const NAME_FACTORY_RESET = 'factory_reset';

	public const NAME_REBOOT = 'reboot';

	public const NAME_TRIGGER = 'trigger';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
