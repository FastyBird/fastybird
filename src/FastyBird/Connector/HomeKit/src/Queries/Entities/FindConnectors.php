<?php declare(strict_types = 1);

/**
 * FindConnectors.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           14.10.23
 */

namespace FastyBird\Connector\HomeKit\Queries\Entities;

use FastyBird\Connector\HomeKit\Entities\Connectors\Connector;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find connectors entities query
 *
 * @template T of Connector
 * @extends  DevicesQueries\Entities\FindConnectors<T>
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConnectors extends DevicesQueries\Entities\FindConnectors
{

}
