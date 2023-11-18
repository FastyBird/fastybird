<?php declare(strict_types = 1);

/**
 * MqttFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;

/**
 * MQTT client factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface MqttFactory extends ClientFactory
{

	public const MODE = Types\ClientMode::MQTT;

	public function create(MetadataDocuments\DevicesModule\Connector $connector): Mqtt;

}
