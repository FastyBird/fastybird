<?php declare(strict_types = 1);

/**
 * FbMqttConnector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Schemas
 * @since          0.4.0
 *
 * @date           20.02.21
 */

namespace FastyBird\Connector\FbMqtt\Schemas;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * FastyBird MQTT connector entity schema
 *
 * @phpstan-extends DevicesSchemas\Connectors\Connector<Entities\FbMqttConnector>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class FbMqttConnector extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT . '/connector/' . Entities\FbMqttConnector::CONNECTOR_TYPE;

	public function getEntityClass(): string
	{
		return Entities\FbMqttConnector::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
