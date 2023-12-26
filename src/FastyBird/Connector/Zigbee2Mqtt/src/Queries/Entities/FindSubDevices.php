<?php declare(strict_types = 1);

/**
 * FindSubDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queries\Entities;

use FastyBird\Connector\Zigbee2Mqtt\Entities;

/**
 * Find sub-devices entities query
 *
 * @template T of Entities\Devices\SubDevice
 * @extends  FindDevices<T>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindSubDevices extends FindDevices
{

}
