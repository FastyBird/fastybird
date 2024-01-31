<?php declare(strict_types = 1);

/**
 * ThermostatFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Drivers
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Addon\VirtualThermostatDevice\Drivers;

use FastyBird\Addon\VirtualThermostatDevice\Entities;
use FastyBird\Connector\Virtual\Drivers\DriverFactory;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;

/**
 * Thermostat service factory
 *
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Drivers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ThermostatFactory extends DriverFactory
{

	public const DEVICE_TYPE = Entities\ThermostatDevice::TYPE;

	public function create(MetadataDocuments\DevicesModule\Device $device): Thermostat;

}
