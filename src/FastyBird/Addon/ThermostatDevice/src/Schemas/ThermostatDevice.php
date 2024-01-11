<?php declare(strict_types = 1);

/**
 * ThermostatDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Addon\ThermostatDevice\Schemas;

use FastyBird\Addon\ThermostatDevice\Entities;
use FastyBird\Connector\Virtual\Schemas as VirtualSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Thermostat device entity schema
 *
 * @template T of Entities\ThermostatDevice
 * @extends  VirtualSchemas\VirtualDevice<T>
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ThermostatDevice extends VirtualSchemas\VirtualDevice
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL . '/device/' . Entities\ThermostatDevice::TYPE;

	public function getEntityClass(): string
	{
		return Entities\ThermostatDevice::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
