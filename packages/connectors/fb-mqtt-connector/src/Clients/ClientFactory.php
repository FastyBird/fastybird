<?php declare(strict_types = 1);

/**
 * ClientFactory.php
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

use FastyBird\Metadata\Entities as MetadataEntities;

/**
 * Base client factory
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ClientFactory
{

	public const VERSION_CONSTANT_NAME = 'VERSION';

	public function create(MetadataEntities\DevicesModule\Connector $connector): Client;

}
