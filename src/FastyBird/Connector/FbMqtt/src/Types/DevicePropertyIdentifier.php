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

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Device property identifier types
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum DevicePropertyIdentifier: string
{

	case STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	case IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	case STATUS_LED = DevicesTypes\DevicePropertyIdentifier::STATUS_LED->value;

	case UPTIME = DevicesTypes\DevicePropertyIdentifier::UPTIME->value;

	case FREE_HEAP = DevicesTypes\DevicePropertyIdentifier::FREE_HEAP->value;

	case CPU_LOAD = DevicesTypes\DevicePropertyIdentifier::CPU_LOAD->value;

	case VCC = DevicesTypes\DevicePropertyIdentifier::VCC->value;

	case RSSI = DevicesTypes\DevicePropertyIdentifier::RSSI->value;

	case HARDWARE_MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	case HARDWARE_MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER->value;

	case HARDWARE_MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	case HARDWARE_VERSION = DevicesTypes\DevicePropertyIdentifier::HARDWARE_VERSION->value;

	case FIRMWARE_MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_MANUFACTURER->value;

	case FIRMWARE_NAME = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_NAME->value;

	case FIRMWARE_VERSION = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_VERSION->value;

}
