<?php declare(strict_types = 1);

/**
 * ConnectorFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Connector
 * @since          0.37.0
 *
 * @date           14.07.22
 */

namespace FastyBird\Connector\Shelly\Connector;

use FastyBird\Connector\Shelly\Connector;
use FastyBird\DevicesModule\Connectors as DevicesModuleConnectors;
use FastyBird\Library\Metadata\Entities as MetadataEntities;

/**
 * Connector service executor factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ConnectorFactory extends DevicesModuleConnectors\ConnectorFactory
{

	public function create(
		MetadataEntities\DevicesModule\Connector $connector,
	): Connector\Connector;

}
