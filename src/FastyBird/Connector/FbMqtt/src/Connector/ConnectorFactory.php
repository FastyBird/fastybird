<?php declare(strict_types = 1);

/**
 * ConnectorFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Connector
 * @since          0.25.0
 *
 * @date           23.07.22
 */

namespace FastyBird\Connector\FbMqtt\Connector;

use FastyBird\Connector\FbMqtt\Connector;
use FastyBird\DevicesModule\Connectors as DevicesModuleConnectors;
use FastyBird\Metadata\Entities as MetadataEntities;

/**
 * Connector service executor factory
 *
 * @package        FastyBird:FbMqttConnector!
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
