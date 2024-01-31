<?php declare(strict_types = 1);

/**
 * ThermostatChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           11.01.24
 */

namespace FastyBird\Addon\VirtualThermostatDevice\Hydrators;

use FastyBird\Addon\VirtualThermostatDevice\Entities;
use FastyBird\Connector\Virtual\Hydrators as VirtualHydrators;

/**
 * Virtual thermostat channel entity schema
 *
 * @template T of Entities\ThermostatChannel
 * @extends  VirtualHydrators\VirtualChannel<T>
 *
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class ThermostatChannel extends VirtualHydrators\VirtualChannel
{

}
