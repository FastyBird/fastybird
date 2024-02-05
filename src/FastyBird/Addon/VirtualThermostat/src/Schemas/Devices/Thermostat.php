<?php declare(strict_types = 1);

/**
 * VirtualThermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Schemas\Devices;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Schemas;
use FastyBird\Connector\Virtual\Schemas as VirtualSchemas;
use FastyBird\Connector\Virtual\Schemas\Devices\Device;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Thermostat device entity schema
 *
 * @template T of Entities\Devices\Thermostat
 * @extends  Device<T>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Thermostat extends VirtualSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT . '/device/' . Entities\Devices\Thermostat::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Thermostat::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
