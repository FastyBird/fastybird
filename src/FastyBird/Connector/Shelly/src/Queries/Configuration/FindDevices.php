<?php declare(strict_types = 1);

/**
 * FindDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           23.08.23
 */

namespace FastyBird\Connector\Shelly\Queries\Configuration;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find devices entities query
 *
 * @template T of Documents\Devices\Device
 * @extends  DevicesQueries\Configuration\FindDevices<T>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDevices extends DevicesQueries\Configuration\FindDevices
{

}
