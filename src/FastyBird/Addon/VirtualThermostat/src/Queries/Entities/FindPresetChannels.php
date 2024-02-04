<?php declare(strict_types = 1);

/**
 * FindPresetChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           26.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Queries\Entities;

use FastyBird\Addon\VirtualThermostat\Entities;

/**
 * Find device presets channels entities query
 *
 * @template T of Entities\Channels\Preset
 * @extends  FindChannels<T>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindPresetChannels extends FindChannels
{

}
