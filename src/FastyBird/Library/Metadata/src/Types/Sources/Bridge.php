<?php declare(strict_types = 1);

/**
 * Bridge.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Library\Metadata\Types\Sources;

use FastyBird\Library\Metadata;

/**
 * Bridges sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Bridge: string implements Source
{

	case NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	case REDISDB_PLUGIN_DEVICES_MODULE = Metadata\Constants::BRIDGE_REDISDB_PLUGIN_DEVICES_MODULE;

	case REDISDB_PLUGIN_TRIGGERS_MODULE = Metadata\Constants::BRIDGE_REDISDB_PLUGIN_TRIGGERS_MODULE;

	case VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR = Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR;

}
