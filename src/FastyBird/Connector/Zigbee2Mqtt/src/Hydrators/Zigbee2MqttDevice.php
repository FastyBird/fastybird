<?php declare(strict_types = 1);

/**
 * Zigbee2MqttDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Hydrators;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Zigbee2MQTT device entity hydrator
 *
 * @template  T of Entities\Zigbee2MqttDevice
 * @extends   DevicesHydrators\Devices\Device<T>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Zigbee2MqttDevice extends DevicesHydrators\Devices\Device
{

}
