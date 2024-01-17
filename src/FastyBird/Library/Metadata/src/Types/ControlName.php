<?php declare(strict_types = 1);

/**
 * ControlName.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           29.09.21
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Control name types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ControlName extends Consistence\Enum\Enum
{

	/**
	 * Define controls names
	 */
	public const CONFIGURE = 'configure';

	public const RESET = 'reset';

	public const FACTORY_RESET = 'factory_reset';

	public const REBOOT = 'reboot';

	public const TRIGGER = 'trigger';

	public const DISCOVER = 'discover';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
