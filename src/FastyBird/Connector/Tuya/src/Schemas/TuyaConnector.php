<?php declare(strict_types = 1);

/**
 * Tuya.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           25.04.22
 */

namespace FastyBird\Connector\Tuya\Schemas;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Tuya connector entity schema
 *
 * @extends DevicesSchemas\Connectors\Connector<Entities\TuyaConnector>
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TuyaConnector extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA . '/connector/' . Entities\TuyaConnector::TYPE;

	public function getEntityClass(): string
	{
		return Entities\TuyaConnector::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
