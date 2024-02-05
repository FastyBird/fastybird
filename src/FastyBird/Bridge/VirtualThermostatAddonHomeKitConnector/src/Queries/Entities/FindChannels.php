<?php declare(strict_types = 1);

/**
 * FindChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries\Entities;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device channels entities query
 *
 * @template T of Entities\Channels\Thermostat
 * @extends  HomeKitQueries\Entities\FindChannels<T>
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindChannels extends HomeKitQueries\Entities\FindChannels
{

}
