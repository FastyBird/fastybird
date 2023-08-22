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

use Consistence;
use function in_array;
use function strval;

/**
 * Capability types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Capability extends Consistence\Enum\Enum
{

	public const POWER = 'power';

	public const TOGGLE = 'toggle';

	public const BRIGHTNESS = 'brightness';

	public const COLOR_TEMPERATURE = 'color-temperature';

	public const COLOR_RGB = 'color-rgb';

	public const PERCENTAGE = 'percentage';

	public const MOTOR_CONTROL = 'motor-control';

	public const MOTOR_REVERSE = 'motor-reverse';

	public const MOTOR_CALIBRATION = 'motor-clb';

	public const STARTUP = 'startup';

	public const CAMERA_STREAM = 'camera-stream';

	public const DETECT = 'detect';

	public const HUMIDITY = 'humidity';

	public const TEMPERATURE = 'temperature';

	public const BATTERY = 'battery';

	public const PRESS = 'press';

	public const RSSI = 'rssi';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	/**
	 * @return array<string>
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
	 * @return array<string>
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
		return in_array(self::getValue(), self::getReadWrite(), true);
	}

	public function hasReadPermission(): bool
	{
		return in_array(self::getValue(), self::getRead(), true);
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
