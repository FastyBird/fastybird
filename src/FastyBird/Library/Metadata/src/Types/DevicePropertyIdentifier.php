<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
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
 * Device property identifier types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device properties identifiers
	 */
	public const IDENTIFIER_STATE = PropertyIdentifier::IDENTIFIER_STATE;

	public const IDENTIFIER_BATTERY = PropertyIdentifier::IDENTIFIER_BATTERY;

	public const IDENTIFIER_WIFI = PropertyIdentifier::IDENTIFIER_WIFI;

	public const IDENTIFIER_SIGNAL = PropertyIdentifier::IDENTIFIER_SIGNAL;

	public const IDENTIFIER_RSSI = PropertyIdentifier::IDENTIFIER_RSSI;

	public const IDENTIFIER_SSID = PropertyIdentifier::IDENTIFIER_SSID;

	public const IDENTIFIER_VCC = PropertyIdentifier::IDENTIFIER_VCC;

	public const IDENTIFIER_CPU_LOAD = PropertyIdentifier::IDENTIFIER_CPU_LOAD;

	public const IDENTIFIER_UPTIME = PropertyIdentifier::IDENTIFIER_UPTIME;

	public const IDENTIFIER_ADDRESS = PropertyIdentifier::IDENTIFIER_ADDRESS;

	public const IDENTIFIER_IP_ADDRESS = PropertyIdentifier::IDENTIFIER_IP_ADDRESS;

	public const IDENTIFIER_DOMAIN = PropertyIdentifier::IDENTIFIER_DOMAIN;

	public const IDENTIFIER_STATUS_LED = PropertyIdentifier::IDENTIFIER_STATUS_LED;

	public const IDENTIFIER_FREE_HEAP = PropertyIdentifier::IDENTIFIER_FREE_HEAP;

	public const IDENTIFIER_HARDWARE_MANUFACTURER = PropertyIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER;

	public const IDENTIFIER_HARDWARE_MODEL = PropertyIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public const IDENTIFIER_HARDWARE_VERSION = PropertyIdentifier::IDENTIFIER_HARDWARE_VERSION;

	public const IDENTIFIER_HARDWARE_MAC_ADDRESS = PropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

	public const IDENTIFIER_FIRMWARE_MANUFACTURER = PropertyIdentifier::IDENTIFIER_FIRMWARE_MANUFACTURER;

	public const IDENTIFIER_FIRMWARE_NAME = PropertyIdentifier::IDENTIFIER_FIRMWARE_NAME;

	public const IDENTIFIER_FIRMWARE_VERSION = PropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION;

	public const IDENTIFIER_SERIAL_NUMBER = PropertyIdentifier::IDENTIFIER_SERIAL_NUMBER;

	public const IDENTIFIER_STATE_READING_DELAY = PropertyIdentifier::IDENTIFIER_STATE_READING_DELAY;

	public const IDENTIFIER_STATE_PROCESSING_DELAY = PropertyIdentifier::IDENTIFIER_STATE_PROCESSING_DELAY;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
