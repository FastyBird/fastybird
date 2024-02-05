<?php declare(strict_types = 1);

/**
 * Actors.php
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
use FastyBird\Connector\Virtual\Hydrators\Channels\Channel;

/**
 * Actors channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Actors>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Actors extends VirtualHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Actors::class;
	}

}
