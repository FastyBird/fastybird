<?php declare(strict_types = 1);

/**
 * FindConnectors.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           05.12.23
 */

namespace FastyBird\Connector\FbMqtt\Queries\Entities;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find connectors entities query
 *
 * @template T of Entities\FbMqttConnector
 * @extends  DevicesQueries\Entities\FindConnectors<T>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConnectors extends DevicesQueries\Entities\FindConnectors
{

}
