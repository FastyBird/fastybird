<?php declare(strict_types = 1);

/**
 * ThermostatFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Services
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Connector\Virtual\Drivers;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;

/**
 * Thermostat service factory
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Services
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ThermostatFactory extends DriverFactory
{

	public const DEVICE_TYPE = Entities\Devices\Thermostat::TYPE;

	public function create(MetadataDocuments\DevicesModule\Device $device): Thermostat;

}
