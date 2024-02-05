<?php declare(strict_types = 1);

/**
 * ModbusDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           22.01.22
 */

namespace FastyBird\Connector\Modbus\Schemas;

use FastyBird\Connector\Modbus\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Modbus device entity schema
 *
 * @extends DevicesSchemas\Devices\Device<Entities\ModbusDevice>
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ModbusDevice extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Connector::MODBUS . '/device/' . Entities\ModbusDevice::TYPE;

	public function getEntityClass(): string
	{
		return Entities\ModbusDevice::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
