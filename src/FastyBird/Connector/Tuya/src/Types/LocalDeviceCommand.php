<?php declare(strict_types = 1);

/**
 * LocalDeviceCommand.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           07.09.22
 */

namespace FastyBird\Connector\Tuya\Types;

use Consistence;
use function intval;
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

	public const UDP = 0;

	public const AP_CONFIG = 1;

	public const ACTIVE = 2;

	public const SESS_KEY_NEG_START = 3;

	public const SESS_KEY_NEG_RESP = 4;

	public const SESS_KEY_NEG_FINISH = 5;

	public const UNBIND = 6;

	public const CONTROL = 7;

	public const STATUS = 8;

	public const HEART_BEAT = 9;

	public const DP_QUERY = 10;

	public const QUERY_WIFI = 11;

	public const TOKEN_BIND = 12;

	public const CONTROL_NEW = 13;

	public const ENABLE_WIFI = 14;

	public const DP_QUERY_NEW = 16;

	public const SCENE_EXECUTE = 17;

	public const UPDATE_DPS = 18;

	public const UDP_NEW = 19;

	public const AP_CONFIG_NEW = 20;

	public const LAN_GW_ACTIVE = 240;

	public const LAN_SUB_DEV_REQUEST = 241;

	public const LAN_DELETE_SUB_DEV = 242;

	public const LAN_REPORT_SUB_DEV = 243;

	public const LAN_SCENE = 244;

	public const LAN_PUBLISH_CLOUD_CONFIG = 245;

	public const LAN_PUBLISH_APP_CONFIG = 246;

	public const LAN_EXPORT_APP_CONFIG = 247;

	public const LAN_PUBLISH_SCENE_PANEL = 248;

	public const LAN_REMOVE_GW = 249;

	public const LAN_CHECK_GW_UPDATE = 250;

	public const LAN_GW_UPDATE = 251;

	public const LAN_SET_GW_CHANNEL = 252;

	public const UNKNOWN = -1;

	public function getValue(): int
	{
		return intval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
