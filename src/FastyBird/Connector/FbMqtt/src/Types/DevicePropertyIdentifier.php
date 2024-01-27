<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           23.07.22
 */

namespace FastyBird\Connector\FbMqtt\Types;

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device property identifier types
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const STATE = MetadataTypes\DevicePropertyIdentifier::STATE;

	public const IP_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IP_ADDRESS;

	public const STATUS_LED = MetadataTypes\DevicePropertyIdentifier::STATUS_LED;

	public const UPTIME = MetadataTypes\DevicePropertyIdentifier::UPTIME;

	public const FREE_HEAP = MetadataTypes\DevicePropertyIdentifier::FREE_HEAP;

	public const CPU_LOAD = MetadataTypes\DevicePropertyIdentifier::CPU_LOAD;

	public const VCC = MetadataTypes\DevicePropertyIdentifier::VCC;

	public const RSSI = MetadataTypes\DevicePropertyIdentifier::RSSI;

	public const HARDWARE_MAC_ADDRESS = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS;

	public const HARDWARE_MANUFACTURER = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER;

	public const HARDWARE_MODEL = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MODEL;

	public const HARDWARE_VERSION = MetadataTypes\DevicePropertyIdentifier::HARDWARE_VERSION;

	public const FIRMWARE_MANUFACTURER = MetadataTypes\DevicePropertyIdentifier::FIRMWARE_MANUFACTURER;

	public const FIRMWARE_NAME = MetadataTypes\DevicePropertyIdentifier::FIRMWARE_NAME;

	public const FIRMWARE_VERSION = MetadataTypes\DevicePropertyIdentifier::FIRMWARE_VERSION;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
