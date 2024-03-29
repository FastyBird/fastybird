<?php declare(strict_types = 1);

/**
 * FindConnectors.php
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

namespace FastyBird\Connector\Shelly\Queries\Entities;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find connectors entities query
 *
 * @template T of Entities\Connectors\Connector
 * @extends  DevicesQueries\Entities\FindConnectors<T>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConnectors extends DevicesQueries\Entities\FindConnectors
{

}
