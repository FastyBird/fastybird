<?php declare(strict_types = 1);

/**
 * VirtualConnector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Schemas;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Virtual connector entity schema
 *
 * @extends DevicesSchemas\Connectors\Connector<Entities\VirtualConnector>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class VirtualConnector extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL . '/connector/' . Entities\VirtualConnector::TYPE;

	public function getEntityClass(): string
	{
		return Entities\VirtualConnector::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
