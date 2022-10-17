<?php declare(strict_types = 1);

/**
 * Gen1Factory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.37.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Types;
use FastyBird\Metadata\Entities as MetadataEntities;

/**
 * Generation 1 devices client factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Gen1Factory extends ClientFactory
{

	public const VERSION = Types\ClientVersion::TYPE_GEN_1;

	public function create(MetadataEntities\DevicesModule\Connector $connector): Gen1;

}
