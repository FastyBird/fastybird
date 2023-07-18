<?php declare(strict_types = 1);

/**
 * DeviceCapability.php
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

	/**
	 * Permissions
	 */
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

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
