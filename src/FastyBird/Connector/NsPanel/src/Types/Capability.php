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

use function in_array;

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

	/**
	 * @return array<Capability>
	 */
	public function getReadWrite(): array
	{
		return [
			self::POWER,
			self::TOGGLE,
			self::BRIGHTNESS,
			self::COLOR_TEMPERATURE,
			self::COLOR_RGB,
			self::PERCENTAGE,
			self::MOTOR_CONTROL,
			self::MOTOR_REVERSE,
			self::STARTUP,
		];
	}

	/**
	 * @return array<Capability>
	 */
	public function getRead(): array
	{
		return [
			self::MOTOR_CALIBRATION,
			self::CAMERA_STREAM,
			self::DETECT,
			self::HUMIDITY,
			self::TEMPERATURE,
			self::BATTERY,
			self::PRESS,
			self::RSSI,
		];
	}

	public function hasReadWritePermission(): bool
	{
		return in_array($this, self::getReadWrite(), true);
	}

	public function hasReadPermission(): bool
	{
		return in_array($this, self::getRead(), true);
	}

}
