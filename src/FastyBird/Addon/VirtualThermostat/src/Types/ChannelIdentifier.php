<?php declare(strict_types = 1);

/**
 * ChannelIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * Channel identifier types
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ChannelIdentifier: string
{

	case CONFIGURATION = 'configuration';

	case STATE = 'state';

	case PRESET_MANUAL = 'preset_manual';

	case PRESET_AWAY = 'preset_away';

	case PRESET_ECO = 'preset_eco';

	case PRESET_HOME = 'preset_home';

	case PRESET_COMFORT = 'preset_comfort';

	case PRESET_SLEEP = 'preset_sleep';

	case PRESET_ANTI_FREEZE = 'preset_anti_freeze';

	case SENSORS = 'sensors';

	case ACTORS = 'actors';

	case OPENINGS = 'openings';

}
