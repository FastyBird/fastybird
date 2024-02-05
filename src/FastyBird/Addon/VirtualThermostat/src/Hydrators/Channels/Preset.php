<?php declare(strict_types = 1);

/**
 * Preset.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Hydrators\Channels;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Hydrators;
use FastyBird\Connector\Virtual\Hydrators as VirtualHydrators;

/**
 * Preset channel entity hydrator
 *
 * @extends VirtualHydrators\VirtualChannel<Entities\Channels\Preset>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Preset extends VirtualHydrators\VirtualChannel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Preset::class;
	}

}
