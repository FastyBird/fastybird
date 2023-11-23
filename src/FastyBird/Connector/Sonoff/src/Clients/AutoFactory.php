<?php declare(strict_types = 1);

/**
 * AutoFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           08.05.23
 */

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;

/**
 * Automatic selection devices client factory
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface AutoFactory extends ClientFactory
{

	public const MODE = Types\ClientMode::AUTO;

	public function create(MetadataDocuments\DevicesModule\Connector $connector): Auto;

}
