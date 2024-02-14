<?php declare(strict_types = 1);

/**
 * ControlName.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           29.09.21
 */

namespace FastyBird\Module\Triggers\Types;

/**
 * Control name types
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ControlName: string
{

	case TRIGGER = 'trigger';

}
