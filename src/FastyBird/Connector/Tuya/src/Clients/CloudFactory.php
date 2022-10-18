<?php declare(strict_types = 1);

/**
 * CloudFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          0.13.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Metadata\Entities as MetadataEntities;

/**
 * Cloud devices client factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface CloudFactory extends ClientFactory
{

	public const MODE = Types\ClientMode::MODE_CLOUD;

	public function create(MetadataEntities\DevicesModule\Connector $connector): Cloud;

}
