<?php declare(strict_types = 1);

/**
 * LocalFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;

/**
 * Local devices client factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface LocalFactory extends ClientFactory
{

	public const MODE = Types\ClientMode::LOCAL;

	public function create(MetadataDocuments\DevicesModule\Connector $connector): Local;

}
