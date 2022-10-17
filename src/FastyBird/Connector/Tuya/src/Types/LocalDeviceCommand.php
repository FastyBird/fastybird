<?php declare(strict_types = 1);

/**
 * LocalDeviceCommand.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          0.13.0
 *
 * @date           07.09.22
 */

namespace FastyBird\Connector\Tuya\Types;

use Consistence;
use function strval;

/**
 * Local device command codes
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class LocalDeviceCommand extends Consistence\Enum\Enum
{

	/**
	 * Define codes
	 */
	public const CMD_UDP = 0;

	public const CMD_AP_CONFIG = 1;

	public const CMD_ACTIVE = 2;

	public const CMD_BIND = 3;

	public const CMD_RENAME_GW = 4;

	public const CMD_RENAME_DEVICE = 5;

	public const CMD_UNBIND = 6;

	public const CMD_CONTROL = 7;

	public const CMD_STATUS = 8;

	public const CMD_HEART_BEAT = 9;

	public const CMD_DP_QUERY = 10;

	public const CMD_QUERY_WIFI = 11;

	public const CMD_TOKEN_BIND = 12;

	public const CMD_CONTROL_NEW = 13;

	public const CMD_ENABLE_WIFI = 14;

	public const CMD_DP_QUERY_NEW = 16;

	public const CMD_SCENE_EXECUTE = 17;

	public const CMD_UDP_NEW = 19;

	public const CMD_AP_CONFIG_NEW = 20;

	public const CMD_LAN_GW_ACTIVE = 240;

	public const CMD_LAN_SUB_DEV_REQUEST = 241;

	public const CMD_LAN_DELETE_SUB_DEV = 242;

	public const CMD_LAN_REPORT_SUB_DEV = 243;

	public const CMD_LAN_SCENE = 244;

	public const CMD_LAN_PUBLISH_CLOUD_CONFIG = 245;

	public const CMD_LAN_PUBLISH_APP_CONFIG = 246;

	public const CMD_LAN_EXPORT_APP_CONFIG = 247;

	public const CMD_LAN_PUBLISH_SCENE_PANEL = 248;

	public const CMD_LAN_REMOVE_GW = 249;

	public const CMD_LAN_CHECK_GW_UPDATE = 250;

	public const CMD_LAN_GW_UPDATE = 251;

	public const CMD_LAN_SET_GW_CHANNEL = 252;

	public const CMD_UNKNOWN = -1;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
