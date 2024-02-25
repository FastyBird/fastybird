<?php declare(strict_types = 1);

/**
 * Unit.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           13.01.24
 */

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * Temperature unit types
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Unit: string
{

	case CELSIUS = '°C';

	case FAHRENHEIT = '°F';

}
