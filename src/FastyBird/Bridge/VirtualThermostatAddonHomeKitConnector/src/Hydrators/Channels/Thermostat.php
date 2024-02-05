<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Hydrators as HomeKitHydrators;

/**
 * Thermostat channel entity hydrator
 *
 * @extends HomeKitHydrators\HomeKitChannel<Entities\Channels\Thermostat>
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Thermostat extends HomeKitHydrators\HomeKitChannel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Thermostat::class;
	}

}
