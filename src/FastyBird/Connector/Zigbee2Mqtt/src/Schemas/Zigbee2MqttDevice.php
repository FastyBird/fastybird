<?php declare(strict_types = 1);

/**
 * Zigbee2MqttDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Schemas;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Zigbee2MQTT connector entity schema
 *
 * @template T of Entities\Zigbee2MqttDevice
 * @extends  DevicesSchemas\Devices\Device<T>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Zigbee2MqttDevice extends DevicesSchemas\Devices\Device
{

}
