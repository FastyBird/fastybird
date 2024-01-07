<?php declare(strict_types = 1);

/**
 * Zigbee2MqttChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Schemas;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Zigbee2MQTT device channel entity schema
 *
 * @extends DevicesSchemas\Channels\Channel<Entities\Zigbee2MqttChannel>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Zigbee2MqttChannel extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT . '/channel/' . Entities\Zigbee2MqttChannel::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Zigbee2MqttChannel::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
