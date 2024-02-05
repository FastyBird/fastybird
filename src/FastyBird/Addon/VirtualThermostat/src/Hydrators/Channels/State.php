<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           03.02.24
 */

namespace FastyBird\Addon\VirtualThermostat\Hydrators\Channels;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Hydrators;
use FastyBird\Connector\Virtual\Hydrators as VirtualHydrators;

/**
 * State channel entity hydrator
 *
 * @extends VirtualHydrators\VirtualChannel<Entities\Channels\State>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class State extends VirtualHydrators\VirtualChannel
{

	public function getEntityName(): string
	{
		return Entities\Channels\State::class;
	}

}
