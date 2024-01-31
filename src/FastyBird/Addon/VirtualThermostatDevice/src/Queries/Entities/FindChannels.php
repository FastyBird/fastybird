<?php declare(strict_types = 1);

/**
 * FindChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           11.01.24
 */

namespace FastyBird\Addon\VirtualThermostatDevice\Queries\Entities;

use FastyBird\Addon\VirtualThermostatDevice\Entities;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;

/**
 * Find thermostat channels entities query
 *
 * @template T of Entities\ThermostatChannel
 * @extends  VirtualQueries\Entities\FindChannels<T>
 *
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindChannels extends VirtualQueries\Entities\FindChannels
{

}
