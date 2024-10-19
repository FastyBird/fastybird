<?php declare(strict_types = 1);

/**
 * Group.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           02.10.24
 */

namespace FastyBird\Connector\NsPanel\Types;

/**
 * Capability group types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Group: string
{

	case POWER = 'power';

	case TOGGLE = 'toggle';

	case BRIGHTNESS = 'brightness';

	case COLOR_TEMPERATURE = 'color-temperature';

	case COLOR_RGB = 'color-rgb';

	case PERCENTAGE = 'percentage';

	case MOTOR_CONTROL = 'motor-control';

	case MOTOR_REVERSE = 'motor-reverse';

	case MOTOR_CLB = 'motor-clb';

	case STARTUP_POWER = 'startup-power';

	case STARTUP_TOGGLE = 'startup-toggle';

	case CAMERA_STREAM = 'camera-stream';

	case DETECT = 'detect';

	case HUMIDITY = 'humidity';

	case TEMPERATURE = 'temperature';

	case BATTERY = 'battery';

	case PRESS = 'press';

	case RSSI = 'rssi';

	case ILLUMINATION_LEVEL = 'illumination-level';

	case THERMOSTAT_MODE_DETECT = 'thermostat-mode-detect';

	case THERMOSTAT = 'thermostat';

	case THERMOSTAT_TARGET_SET_POINT = 'thermostat-target-setpoint';

	case FAULT = 'fault';

}
