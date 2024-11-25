<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Core\Application\Documents as ApplicationDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Channels\Thermostat::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Thermostat::TYPE)]
class Thermostat extends HomeKitDocuments\Channels\Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Thermostat::TYPE;
	}

}
