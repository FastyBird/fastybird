<?php declare(strict_types = 1);

/**
 * FbMqttDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Schemas;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * FastyBird MQTT connector entity schema
 *
 * @extends DevicesSchemas\Devices\Device<Entities\FbMqttDevice>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class FbMqttDevice extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::FB_MQTT . '/device/' . Entities\FbMqttDevice::TYPE;

	public function getEntityClass(): string
	{
		return Entities\FbMqttDevice::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
