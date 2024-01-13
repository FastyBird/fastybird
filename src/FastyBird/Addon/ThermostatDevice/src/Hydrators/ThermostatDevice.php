<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Addon\ThermostatDevice\Hydrators;

use FastyBird\Addon\ThermostatDevice\Entities;
use FastyBird\Connector\Virtual\Hydrators as VirtualHydrators;

/**
 * Virtual thermostat device entity hydrator
 *
 * @extends VirtualHydrators\VirtualDevice<Entities\ThermostatDevice>
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ThermostatDevice extends VirtualHydrators\VirtualDevice
{

	public function getEntityName(): string
	{
		return Entities\ThermostatDevice::class;
	}

}
