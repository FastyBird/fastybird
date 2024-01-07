<?php declare(strict_types = 1);

/**
 * SubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Hydrators\Devices;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Hydrators;

/**
 * Zigbee2MQTT sub-device device entity hydrator
 *
 * @extends Hydrators\Zigbee2MqttDevice<Entities\Devices\SubDevice>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SubDevice extends Hydrators\Zigbee2MqttDevice
{

	public function getEntityName(): string
	{
		return Entities\Devices\SubDevice::class;
	}

}
