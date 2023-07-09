<?php declare(strict_types = 1);

/**
 * PropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           16.07.21
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Property identifier types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device properties identifiers
	 */
	public const IDENTIFIER_STATE = 'state';

	public const IDENTIFIER_SERVER = 'server';

	public const IDENTIFIER_PORT = 'port';

	public const IDENTIFIER_SECURED_PORT = 'secured_port';

	public const IDENTIFIER_BAUD_RATE = 'baud_rate';

	public const IDENTIFIER_INTERFACE = 'interface';

	public const IDENTIFIER_ADDRESS = 'address';

	public const IDENTIFIER_BATTERY = 'battery';

	public const IDENTIFIER_WIFI = 'wifi';

	public const IDENTIFIER_SIGNAL = 'signal';

	public const IDENTIFIER_RSSI = 'rssi';

	public const IDENTIFIER_SSID = 'ssid';

	public const IDENTIFIER_VCC = 'vcc';

	public const IDENTIFIER_CPU_LOAD = 'cpu_load';

	public const IDENTIFIER_UPTIME = 'uptime';

	public const IDENTIFIER_IP_ADDRESS = 'ip_address';

	public const IDENTIFIER_STATUS_LED = 'status_led';

	public const IDENTIFIER_FREE_HEAP = 'free_heap';

	public const IDENTIFIER_HARDWARE_MANUFACTURER = 'hardware_manufacturer';

	public const IDENTIFIER_HARDWARE_MODEL = 'hardware_model';

	public const IDENTIFIER_HARDWARE_VERSION = 'hardware_version';

	public const IDENTIFIER_HARDWARE_MAC_ADDRESS = 'hardware_mac_address';

	public const IDENTIFIER_FIRMWARE_MANUFACTURER = 'firmware_manufacturer';

	public const IDENTIFIER_FIRMWARE_NAME = 'firmware_name';

	public const IDENTIFIER_FIRMWARE_VERSION = 'firmware_version';

	public const IDENTIFIER_SERIAL_NUMBER = 'serial_number';

	public const IDENTIFIER_ACCESS_TOKEN = 'access_token';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
