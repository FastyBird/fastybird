<?php declare(strict_types = 1);

/**
 * FbMqttDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Schemas
 * @since          0.4.0
 *
 * @date           05.02.22
 */

namespace FastyBird\FbMqttConnector\Schemas;

use FastyBird\DevicesModule\Schemas as DevicesModuleSchemas;
use FastyBird\FbMqttConnector\Entities;
use FastyBird\Metadata\Types as MetadataTypes;

/**
 * FastyBird MQTT connector entity schema
 *
 * @phpstan-extends DevicesModuleSchemas\Devices\Device<Entities\FbMqttDevice>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class FbMqttDevice extends DevicesModuleSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT . '/device/' . Entities\FbMqttDevice::DEVICE_TYPE;

	public function getEntityClass(): string
	{
		return Entities\FbMqttDevice::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
