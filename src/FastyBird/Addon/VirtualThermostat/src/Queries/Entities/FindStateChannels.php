<?php declare(strict_types = 1);

/**
 * FindStateChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           03.02.24
 */

namespace FastyBird\Addon\VirtualThermostat\Queries\Entities;

use FastyBird\Addon\VirtualThermostat\Entities;

/**
 * Find device thermostat channels entities query
 *
 * @template T of Entities\Channels\State
 * @extends  FindChannels<T>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindStateChannels extends FindChannels
{

}
