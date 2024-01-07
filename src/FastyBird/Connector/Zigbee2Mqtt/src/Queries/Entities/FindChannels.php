<?php declare(strict_types = 1);

/**
 * FindChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queries\Entities;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find device channels entities query
 *
 * @template T of Entities\Zigbee2MqttChannel
 * @extends  DevicesQueries\Entities\FindChannels<T>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindChannels extends DevicesQueries\Entities\FindChannels
{

}
