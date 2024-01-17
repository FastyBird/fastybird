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
	public const STATE = 'state';

	public const SERVER = 'server';

	public const PORT = 'port';

	public const SECURED_PORT = 'secured_port';

	public const BAUD_RATE = 'baud_rate';

	public const INTERFACE = 'interface';

	public const ADDRESS = 'address';

	public const BATTERY = 'battery';

	public const WIFI = 'wifi';

	public const SIGNAL = 'signal';

	public const RSSI = 'rssi';

	public const SSID = 'ssid';

	public const VCC = 'vcc';

	public const CPU_LOAD = 'cpu_load';

	public const UPTIME = 'uptime';

	public const IP_ADDRESS = 'ip_address';

	public const DOMAIN = 'domain';

	public const STATUS_LED = 'status_led';

	public const FREE_HEAP = 'free_heap';

	public const HARDWARE_MANUFACTURER = 'hardware_manufacturer';

	public const HARDWARE_MODEL = 'hardware_model';

	public const HARDWARE_VERSION = 'hardware_version';

	public const HARDWARE_MAC_ADDRESS = 'hardware_mac_address';

	public const FIRMWARE_MANUFACTURER = 'firmware_manufacturer';

	public const FIRMWARE_NAME = 'firmware_name';

	public const FIRMWARE_VERSION = 'firmware_version';

	public const SERIAL_NUMBER = 'serial_number';

	public const ACCESS_TOKEN = 'access_token';

	public const STATE_READING_DELAY = 'state_reading_delay';

	public const STATE_PROCESSING_DELAY = 'state_processing_delay';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
