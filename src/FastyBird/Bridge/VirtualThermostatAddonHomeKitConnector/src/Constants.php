<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector;

/**
 * Bridge constants
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	public const MODEL = 'Virtual Thermostat Bridge';

	public const MANUFACTURER = 'FastyBird';

	public const ROUTE_NAME_BRIDGES = 'devices';

	public const ROUTE_NAME_BRIDGE = 'device';

	public const ROUTE_NAME_BRIDGE_RELATIONSHIP = 'device.relationship';

}
