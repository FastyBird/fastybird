<?php declare(strict_types = 1);

/**
 * Connector.php
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

namespace FastyBird\Connector\Zigbee2Mqtt\Hydrators\Connectors;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Zigbee2MQTT connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\Connectors\Connector>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\Connectors\Connector::class;
	}

}
