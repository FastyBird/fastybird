<?php declare(strict_types = 1);

/**
 * FindThermostatDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Queries;

use FastyBird\Connector\Virtual\Entities;

/**
 * Find thermostat devices entities query
 *
 * @template T of Entities\Devices\Thermostat
 * @extends  FindDevices<T>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindThermostatDevices extends FindDevices
{

}
