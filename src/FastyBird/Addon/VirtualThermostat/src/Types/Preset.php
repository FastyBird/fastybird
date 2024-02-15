<?php declare(strict_types = 1);

/**
 * Preset.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           15.10.22
 */

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * Presets types
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Preset: string
{

	case AUTO = 'auto';

	case MANUAL = 'manual';

	case AWAY = 'away';

	case ECO = 'eco';

	case HOME = 'home';

	case COMFORT = 'comfort';

	case SLEEP = 'sleep';

	case ANTI_FREEZE = 'anti_freeze';

}
