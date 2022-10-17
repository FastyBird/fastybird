<?php declare(strict_types = 1);

/**
 * ShellyDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Schemas
 * @since          0.1.0
 *
 * @date           22.01.22
 */

namespace FastyBird\Connector\Shelly\Schemas;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\DevicesModule\Schemas as DevicesModuleSchemas;
use FastyBird\Metadata\Types as MetadataTypes;

/**
 * Shelly connector entity schema
 *
 * @phpstan-extends DevicesModuleSchemas\Devices\Device<Entities\ShellyDevice>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ShellyDevice extends DevicesModuleSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY . '/device/' . Entities\ShellyDevice::DEVICE_TYPE;

	public function getEntityClass(): string
	{
		return Entities\ShellyDevice::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
