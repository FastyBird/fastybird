<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Hydrators\Devices;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Hydrators;
use FastyBird\Connector\Virtual\Hydrators as VirtualHydrators;
use FastyBird\Connector\Virtual\Hydrators\Devices\Device;

/**
 * Virtual thermostat device entity hydrator
 *
 * @extends Device<Entities\Devices\Thermostat>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Thermostat extends VirtualHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return Entities\Devices\Thermostat::class;
	}

}
