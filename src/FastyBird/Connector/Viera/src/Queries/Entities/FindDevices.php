<?php declare(strict_types = 1);

/**
 * FindDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           10.08.23
 */

namespace FastyBird\Connector\Viera\Queries\Entities;

use FastyBird\Connector\Viera\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find devices entities query
 *
 * @template T of Entities\Devices\Device
 * @extends  DevicesQueries\Entities\FindDevices<T>
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindDevices extends DevicesQueries\Entities\FindDevices
{

}
