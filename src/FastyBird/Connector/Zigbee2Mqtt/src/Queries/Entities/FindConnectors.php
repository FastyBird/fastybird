<?php declare(strict_types = 1);

/**
 * FindConnectors.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queries\Entities;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find connectors entities query
 *
 * @template T of Entities\Zigbee2MqttConnector
 * @extends  DevicesQueries\Entities\FindConnectors<T>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConnectors extends DevicesQueries\Entities\FindConnectors
{

}
