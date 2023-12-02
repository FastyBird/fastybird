<?php declare(strict_types = 1);

/**
 * ServerFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Servers
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Servers;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;

/**
 * Connector base communication server factory
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Servers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ServerFactory
{

	public function create(MetadataDocuments\DevicesModule\Connector $connector): Server;

}
