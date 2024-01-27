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
	public const STATE = PropertyIdentifier::STATE;

	public const BATTERY = PropertyIdentifier::BATTERY;

	public const WIFI = PropertyIdentifier::WIFI;

	public const SIGNAL = PropertyIdentifier::SIGNAL;

	public const RSSI = PropertyIdentifier::RSSI;

	public const SSID = PropertyIdentifier::SSID;

	public const VCC = PropertyIdentifier::VCC;

	public const CPU_LOAD = PropertyIdentifier::CPU_LOAD;

	public const UPTIME = PropertyIdentifier::UPTIME;

	public const ADDRESS = PropertyIdentifier::ADDRESS;

	public const IP_ADDRESS = PropertyIdentifier::IP_ADDRESS;

	public const DOMAIN = PropertyIdentifier::DOMAIN;

	public const STATUS_LED = PropertyIdentifier::STATUS_LED;

	public const FREE_HEAP = PropertyIdentifier::FREE_HEAP;

	public const HARDWARE_MANUFACTURER = PropertyIdentifier::HARDWARE_MANUFACTURER;

	public const HARDWARE_MODEL = PropertyIdentifier::HARDWARE_MODEL;

	public const HARDWARE_VERSION = PropertyIdentifier::HARDWARE_VERSION;

	public const HARDWARE_MAC_ADDRESS = PropertyIdentifier::HARDWARE_MAC_ADDRESS;

	public const FIRMWARE_MANUFACTURER = PropertyIdentifier::FIRMWARE_MANUFACTURER;

	public const FIRMWARE_NAME = PropertyIdentifier::FIRMWARE_NAME;

	public const FIRMWARE_VERSION = PropertyIdentifier::FIRMWARE_VERSION;

	public const SERIAL_NUMBER = PropertyIdentifier::SERIAL_NUMBER;

	public const STATE_READING_DELAY = PropertyIdentifier::STATE_READING_DELAY;

	public const STATE_PROCESSING_DELAY = PropertyIdentifier::STATE_PROCESSING_DELAY;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
