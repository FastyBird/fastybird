<?php declare(strict_types = 1);

/**
 * ConnectorFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Connector
 * @since          0.34.0
 *
 * @date           31.07.22
 */

namespace FastyBird\Connector\Modbus\Connector;

use FastyBird\Connector\Modbus\Connector;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;

/**
 * Connector service executor factory
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Connector
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ConnectorFactory extends DevicesConnectors\ConnectorFactory
{

	public function create(
		MetadataEntities\DevicesModule\Connector $connector,
	): Connector\Connector;

}
