<?php declare(strict_types = 1);

/**
 * FindSensorChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           26.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Queries\Configuration;

use FastyBird\Addon\VirtualThermostat\Documents;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;

/**
 * Find device sensors channels entities query
 *
 * @template T of Documents\Channels\Sensors
 * @extends  VirtualQueries\Configuration\FindChannels<T>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindSensorChannels extends VirtualQueries\Configuration\FindChannels
{

}
