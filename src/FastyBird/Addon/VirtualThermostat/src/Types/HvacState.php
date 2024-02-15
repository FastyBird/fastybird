<?php declare(strict_types = 1);

/**
 * HvacState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           22.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * HVAC state types
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum HvacState: string
{

	case OFF = 'off';

	case COOLING = 'cooling';

	case HEATING = 'heating';

}
