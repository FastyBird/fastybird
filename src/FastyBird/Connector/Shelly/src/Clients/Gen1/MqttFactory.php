<?php declare(strict_types = 1);

/**
 * MqttFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.37.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Clients\Gen1;

use FastyBird\Library\Metadata\Entities as MetadataEntities;

/**
 * MQTT client factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface MqttFactory
{

	public function create(MetadataEntities\DevicesModule\Connector $connector): Mqtt;

}
