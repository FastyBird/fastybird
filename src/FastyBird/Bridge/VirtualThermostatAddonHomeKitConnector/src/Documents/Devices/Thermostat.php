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

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents\Devices;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;

#[DOC\Document(entity: Entities\Devices\Thermostat::class)]
#[DOC\DiscriminatorEntry(name: Entities\Devices\Thermostat::TYPE)]
class Thermostat extends HomeKitDocuments\Devices\Device
{

	public static function getType(): string
	{
		return Entities\Devices\Thermostat::TYPE;
	}

}
