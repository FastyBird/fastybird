<?php declare(strict_types = 1);

/**
 * ControlName.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           29.09.21
 */

namespace FastyBird\Module\Devices\Types;

/**
 * Control name types
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ControlName: string
{

	case CONFIGURE = 'configure';

	case RESET = 'reset';

	case FACTORY_RESET = 'factory_reset';

	case REBOOT = 'reboot';

	case TRIGGER = 'trigger';

	case DISCOVER = 'discover';

}
