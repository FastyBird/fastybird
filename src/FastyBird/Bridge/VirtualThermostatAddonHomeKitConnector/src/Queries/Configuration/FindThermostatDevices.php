<?php declare(strict_types = 1);

/**
 * FindThermostatDevices.php
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

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries\Configuration;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device devices entities query
 *
 * @template T of Documents\Devices\Thermostat
 * @extends  HomeKitQueries\Configuration\FindDevices<T>
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindThermostatDevices extends HomeKitQueries\Configuration\FindDevices
{

}
