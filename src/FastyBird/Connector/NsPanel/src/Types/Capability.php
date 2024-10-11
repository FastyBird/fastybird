<?php declare(strict_types = 1);

/**
 * Capability.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Types;

/**
 * Capability types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Capability: string
{

	case POWER = 'power';

	case TOGGLE = 'toggle';

	case BRIGHTNESS = 'brightness';

	case COLOR_TEMPERATURE = 'color-temperature';

	case COLOR_RGB = 'color-rgb';

	case PERCENTAGE = 'percentage';

	case MOTOR_CONTROL = 'motor-control';

	case MOTOR_REVERSE = 'motor-reverse';

	case MOTOR_CALIBRATION = 'motor-clb';

	case STARTUP = 'startup';

	case CAMERA_STREAM = 'camera-stream';

	case DETECT = 'detect';

	case HUMIDITY = 'humidity';

	case TEMPERATURE = 'temperature';

	case BATTERY = 'battery';

	case PRESS = 'press';

	case RSSI = 'rssi';

	case ILLUMINATION_LEVEL = 'illumination-level';

	case THERMOSTAT_TARGET_SET_POINT = 'thermostat-target-setpoint';

	case THERMOSTAT = 'thermostat';

	case THERMOSTAT_MODE_DETECT = 'thermostat-mode-detect';

	case FAULT = 'fault';

}
