<?php declare(strict_types = 1);

/**
 * FbMqttV1Factory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 * @since          0.25.0
 *
 * @date           05.07.22
 */

namespace FastyBird\FbMqttConnector\Clients;

use FastyBird\FbMqttConnector\Types;
use FastyBird\Metadata\Entities as MetadataEntities;

/**
 * FastyBird MQTT v1 client factory
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface FbMqttV1Factory extends ClientFactory
{

	public const VERSION = Types\ProtocolVersion::VERSION_1;

	public function create(MetadataEntities\DevicesModule\Connector $connector): FbMqttV1;

}
