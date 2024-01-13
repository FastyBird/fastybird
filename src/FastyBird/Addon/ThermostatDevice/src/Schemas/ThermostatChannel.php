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
 * @date           11.01.24
 */

namespace FastyBird\Addon\ThermostatDevice\Schemas;

use FastyBird\Addon\ThermostatDevice\Entities;
use FastyBird\Connector\Virtual\Schemas as VirtualSchemas;

/**
 * Thermostat channel entity schema
 *
 * @template T of Entities\ThermostatChannel
 * @extends  VirtualSchemas\VirtualChannel<T>
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class ThermostatChannel extends VirtualSchemas\VirtualChannel
{

}
