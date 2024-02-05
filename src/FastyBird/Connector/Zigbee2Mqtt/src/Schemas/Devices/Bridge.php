<?php declare(strict_types = 1);

/**
 * Bridge.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Schemas\Devices;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Schemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Zigbee2MQTT bridge entity schema
 *
 * @template T of Entities\Devices\Bridge
 * @extends  Schemas\Zigbee2MqttDevice<T>
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Bridge extends Schemas\Zigbee2MqttDevice
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::ZIGBEE2MQTT . '/device/' . Entities\Devices\Bridge::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Bridge::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
