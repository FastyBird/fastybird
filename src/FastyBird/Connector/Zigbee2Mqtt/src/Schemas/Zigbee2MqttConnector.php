<?php declare(strict_types = 1);

/**
 * Zigbee2MqttConnector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Schemas;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Zigbee2MQTT connector entity schema
 *
 * @extends DevicesSchemas\Connectors\Connector<Entities\Zigbee2MqttConnector>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Zigbee2MqttConnector extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::ZIGBEE2MQTT . '/connector/' . Entities\Zigbee2MqttConnector::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Zigbee2MqttConnector::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
