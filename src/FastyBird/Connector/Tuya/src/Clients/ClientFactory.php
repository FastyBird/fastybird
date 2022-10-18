<?php declare(strict_types = 1);

/**
 * ClientFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          0.13.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Library\Metadata\Entities as MetadataEntities;

/**
 * Base client factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ClientFactory
{

	public const MODE_CONSTANT_NAME = 'MODE';

	public function create(MetadataEntities\DevicesModule\Connector $connector): Client;

}
