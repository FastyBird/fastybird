<?php declare(strict_types = 1);

/**
 * TuyaDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Schemas
 * @since          0.1.0
 *
 * @date           25.04.22
 */

namespace FastyBird\TuyaConnector\Schemas;

use FastyBird\DevicesModule\Schemas as DevicesModuleSchemas;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\TuyaConnector\Entities;

/**
 * Tuya connector entity schema
 *
 * @phpstan-extends DevicesModuleSchemas\Devices\Device<Entities\TuyaDevice>
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TuyaDevice extends DevicesModuleSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA . '/device/' . Entities\TuyaDevice::DEVICE_TYPE;

	public function getEntityClass(): string
	{
		return Entities\TuyaDevice::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
