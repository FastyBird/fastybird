<?php declare(strict_types = 1);

/**
 * ConnectorSource.php
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

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use FastyBird\Library\Metadata;
use function strval;

/**
 * Connectors sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorSource extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	public const CONNECTOR_FB_BUS = Metadata\Constants::CONNECTOR_FB_BUS_SOURCE;

	public const CONNECTOR_FB_MQTT = Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE;

	public const CONNECTOR_SHELLY = Metadata\Constants::CONNECTOR_SHELLY_SOURCE;

	public const CONNECTOR_TUYA = Metadata\Constants::CONNECTOR_TUYA_SOURCE;

	public const CONNECTOR_SONOFF = Metadata\Constants::CONNECTOR_SONOFF_SOURCE;

	public const CONNECTOR_MODBUS = Metadata\Constants::CONNECTOR_MODBUS_SOURCE;

	public const CONNECTOR_HOMEKIT = Metadata\Constants::CONNECTOR_HOMEKIT_SOURCE;

	public const CONNECTOR_VIRTUAL = Metadata\Constants::CONNECTOR_VIRTUAL_SOURCE;

	public const CONNECTOR_TERMINAL = Metadata\Constants::CONNECTOR_TERMINAL_SOURCE;

	public const CONNECTOR_VIERA = Metadata\Constants::CONNECTOR_VIERA_SOURCE;

	public const CONNECTOR_NS_PANEL = Metadata\Constants::CONNECTOR_NS_PANEL_SOURCE;

	public const CONNECTOR_ZIGBEE2MQTT = Metadata\Constants::CONNECTOR_ZIGBEE2MQTT_SOURCE;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
