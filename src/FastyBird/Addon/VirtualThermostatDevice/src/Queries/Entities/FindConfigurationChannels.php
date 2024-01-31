<?php declare(strict_types = 1);

/**
 * FindConfigurationChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           26.10.23
 */

namespace FastyBird\Addon\VirtualThermostatDevice\Queries\Entities;

use FastyBird\Addon\VirtualThermostatDevice\Entities;

/**
 * Find device thermostat channels entities query
 *
 * @template T of Entities\Channels\Configuration
 * @extends  FindChannels<T>
 *
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConfigurationChannels extends FindChannels
{

}
