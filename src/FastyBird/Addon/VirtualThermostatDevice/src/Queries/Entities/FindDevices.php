<?php declare(strict_types = 1);

/**
 * FindDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Addon\VirtualThermostatDevice\Queries\Entities;

use FastyBird\Addon\VirtualThermostatDevice\Entities;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;

/**
 * Find thermostat devices entities query
 *
 * @template T of Entities\ThermostatDevice
 * @extends  VirtualQueries\Entities\FindDevices<T>
 *
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDevices extends VirtualQueries\Entities\FindDevices
{

}
