<?php declare(strict_types = 1);

/**
 * DeviceFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Clients\Subscribers;

use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Entities;

/**
 * Device subscriber factory
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface DeviceFactory
{

	public function create(
		Documents\Connectors\Connector|Entities\Connectors\Connector $connector,
	): Device;

}
