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
use FastyBird\Module\Devices\Types as DevicesTypes;
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
	public const STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	public const IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	public const STATUS_LED = DevicesTypes\DevicePropertyIdentifier::STATUS_LED->value;

	public const UPTIME = DevicesTypes\DevicePropertyIdentifier::UPTIME->value;

	public const FREE_HEAP = DevicesTypes\DevicePropertyIdentifier::FREE_HEAP->value;

	public const CPU_LOAD = DevicesTypes\DevicePropertyIdentifier::CPU_LOAD->value;

	public const VCC = DevicesTypes\DevicePropertyIdentifier::VCC->value;

	public const RSSI = DevicesTypes\DevicePropertyIdentifier::RSSI->value;

	public const HARDWARE_MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	public const HARDWARE_MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER->value;

	public const HARDWARE_MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	public const HARDWARE_VERSION = DevicesTypes\DevicePropertyIdentifier::HARDWARE_VERSION->value;

	public const FIRMWARE_MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_MANUFACTURER->value;

	public const FIRMWARE_NAME = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_NAME->value;

	public const FIRMWARE_VERSION = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_VERSION->value;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
