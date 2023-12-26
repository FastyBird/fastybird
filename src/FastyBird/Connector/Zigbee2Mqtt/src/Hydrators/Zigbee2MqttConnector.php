<?php declare(strict_types = 1);

/**
 * Zigbee2Mqtt.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Hydrators;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Zigbee2MQTT Connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\Zigbee2MqttConnector>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Zigbee2MqttConnector extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\Zigbee2MqttConnector::class;
	}

}
