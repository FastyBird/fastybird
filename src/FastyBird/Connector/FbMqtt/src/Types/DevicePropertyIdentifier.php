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
	public const STATE = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE;

	public const IP_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS;

	public const STATUS_LED = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATUS_LED;

	public const UPTIME = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_UPTIME;

	public const FREE_HEAP = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_FREE_HEAP;

	public const CPU_LOAD = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_CPU_LOAD;

	public const VCC = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_VCC;

	public const RSSI = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_RSSI;

	public const HARDWARE_MAC_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

	public const HARDWARE_MANUFACTURER = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER;

	public const HARDWARE_MODEL = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public const HARDWARE_VERSION = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_VERSION;

	public const FIRMWARE_MANUFACTURER = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_MANUFACTURER;

	public const FIRMWARE_NAME = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_NAME;

	public const FIRMWARE_VERSION = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
