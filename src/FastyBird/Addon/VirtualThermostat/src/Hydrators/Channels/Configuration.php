<?php declare(strict_types = 1);

/**
 * Configuration.php
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
 * Configuration channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Configuration>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Configuration extends VirtualHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Configuration::class;
	}

}
