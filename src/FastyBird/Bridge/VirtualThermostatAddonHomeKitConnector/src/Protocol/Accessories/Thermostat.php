<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           12.04.23
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Protocol\Accessories;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Ramsey\Uuid;

/**
 * HAP thermostat device accessory
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Thermostat extends HomeKitProtocol\Accessories\Accessory
{

	public function __construct(
		string $name,
		int|null $aid,
		HomeKitTypes\AccessoryCategory $category,
		private readonly Documents\Devices\Thermostat $device,
	)
	{
		parent::__construct($name, $aid, $category);
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->device->getId();
	}

	public function getDevice(): Documents\Devices\Thermostat
	{
		return $this->device;
	}

}
