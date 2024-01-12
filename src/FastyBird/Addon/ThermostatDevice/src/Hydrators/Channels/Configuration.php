<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Addon\ThermostatDevice\Hydrators\Channels;

use FastyBird\Addon\ThermostatDevice\Entities;
use FastyBird\Addon\ThermostatDevice\Hydrators;

/**
 * Virtual thermostat channel entity hydrator
 *
 * @extends Hydrators\ThermostatChannel<Entities\Channels\Configuration>
 *
 * @package        FastyBird:ThermostatDeviceAddon!
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