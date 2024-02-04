<?php declare(strict_types = 1);

/**
 * VirtualThermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           11.01.24
 */

namespace FastyBird\Addon\VirtualThermostat\Schemas;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Schemas as VirtualSchemas;

/**
 * Thermostat channel entity schema
 *
 * @template T of Entities\ThermostatChannel
 * @extends  VirtualSchemas\VirtualChannel<T>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class ThermostatChannel extends VirtualSchemas\VirtualChannel
{

}
