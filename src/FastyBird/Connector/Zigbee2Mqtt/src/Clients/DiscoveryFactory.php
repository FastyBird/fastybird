<?php declare(strict_types = 1);

/**
 * DiscoveryFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           31.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Clients;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;

/**
 * Connector sub-devices discovery client factory
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface DiscoveryFactory
{

	public function create(MetadataDocuments\DevicesModule\Connector $connector): Discovery;

}
