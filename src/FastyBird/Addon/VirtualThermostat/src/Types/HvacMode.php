<?php declare(strict_types = 1);

/**
 * HvacMode.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * HVAC modes types
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum HvacMode: string
{

	case OFF = 'off';

	case HEAT = 'heat';

	case COOL = 'cool';

	case AUTO = 'auto';

}
