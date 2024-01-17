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
	public const IDENTIFIER_STATE = PropertyIdentifier::STATE;

	public const IDENTIFIER_BATTERY = PropertyIdentifier::BATTERY;

	public const IDENTIFIER_WIFI = PropertyIdentifier::WIFI;

	public const IDENTIFIER_SIGNAL = PropertyIdentifier::SIGNAL;

	public const IDENTIFIER_RSSI = PropertyIdentifier::RSSI;

	public const IDENTIFIER_SSID = PropertyIdentifier::SSID;

	public const IDENTIFIER_VCC = PropertyIdentifier::VCC;

	public const IDENTIFIER_CPU_LOAD = PropertyIdentifier::CPU_LOAD;

	public const IDENTIFIER_UPTIME = PropertyIdentifier::UPTIME;

	public const IDENTIFIER_ADDRESS = PropertyIdentifier::ADDRESS;

	public const IDENTIFIER_IP_ADDRESS = PropertyIdentifier::IP_ADDRESS;

	public const IDENTIFIER_DOMAIN = PropertyIdentifier::DOMAIN;

	public const IDENTIFIER_STATUS_LED = PropertyIdentifier::STATUS_LED;

	public const IDENTIFIER_FREE_HEAP = PropertyIdentifier::FREE_HEAP;

	public const IDENTIFIER_HARDWARE_MANUFACTURER = PropertyIdentifier::HARDWARE_MANUFACTURER;

	public const IDENTIFIER_HARDWARE_MODEL = PropertyIdentifier::HARDWARE_MODEL;

	public const IDENTIFIER_HARDWARE_VERSION = PropertyIdentifier::HARDWARE_VERSION;

	public const IDENTIFIER_HARDWARE_MAC_ADDRESS = PropertyIdentifier::HARDWARE_MAC_ADDRESS;

	public const IDENTIFIER_FIRMWARE_MANUFACTURER = PropertyIdentifier::FIRMWARE_MANUFACTURER;

	public const IDENTIFIER_FIRMWARE_NAME = PropertyIdentifier::FIRMWARE_NAME;

	public const IDENTIFIER_FIRMWARE_VERSION = PropertyIdentifier::FIRMWARE_VERSION;

	public const IDENTIFIER_SERIAL_NUMBER = PropertyIdentifier::SERIAL_NUMBER;

	public const IDENTIFIER_STATE_READING_DELAY = PropertyIdentifier::STATE_READING_DELAY;

	public const IDENTIFIER_STATE_PROCESSING_DELAY = PropertyIdentifier::STATE_PROCESSING_DELAY;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
