<?php declare(strict_types = 1);

/**
 * Zigbee2MqttChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Hydrators;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Zigbee2MQTT channel entity hydrator
 *
 * @extends DevicesHydrators\Channels\Channel<Entities\Zigbee2MqttChannel>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Zigbee2MqttChannel extends DevicesHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\Zigbee2MqttChannel::class;
	}

}
