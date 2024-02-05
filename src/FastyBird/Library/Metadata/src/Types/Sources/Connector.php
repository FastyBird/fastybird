<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           19.01.22
 */

namespace FastyBird\Library\Metadata\Types\Sources;

use FastyBird\Library\Metadata;

/**
 * Connectors sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Connector extends Source
{

	/**
	 * Define types
	 */
	public const NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	public const FB_BUS = Metadata\Constants::CONNECTOR_FB_BUS_SOURCE;

	public const FB_MQTT = Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE;

	public const SHELLY = Metadata\Constants::CONNECTOR_SHELLY_SOURCE;

	public const TUYA = Metadata\Constants::CONNECTOR_TUYA_SOURCE;

	public const SONOFF = Metadata\Constants::CONNECTOR_SONOFF_SOURCE;

	public const MODBUS = Metadata\Constants::CONNECTOR_MODBUS_SOURCE;

	public const HOMEKIT = Metadata\Constants::CONNECTOR_HOMEKIT_SOURCE;

	public const VIRTUAL = Metadata\Constants::CONNECTOR_VIRTUAL_SOURCE;

	public const TERMINAL = Metadata\Constants::CONNECTOR_TERMINAL_SOURCE;

	public const VIERA = Metadata\Constants::CONNECTOR_VIERA_SOURCE;

	public const NS_PANEL = Metadata\Constants::CONNECTOR_NS_PANEL_SOURCE;

	public const ZIGBEE2MQTT = Metadata\Constants::CONNECTOR_ZIGBEE2MQTT_SOURCE;

}
