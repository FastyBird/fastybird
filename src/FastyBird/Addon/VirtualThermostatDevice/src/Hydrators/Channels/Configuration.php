<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Addon\VirtualThermostatDevice\Hydrators\Channels;

use FastyBird\Addon\VirtualThermostatDevice\Entities;
use FastyBird\Addon\VirtualThermostatDevice\Hydrators;

/**
 * Virtual thermostat channel entity hydrator
 *
 * @extends Hydrators\ThermostatChannel<Entities\Channels\Configuration>
 *
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Configuration extends Hydrators\ThermostatChannel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Configuration::class;
	}

}