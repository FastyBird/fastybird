<?php declare(strict_types = 1);

/**
 * Protocol.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           22.07.23
 */

namespace FastyBird\Connector\NsPanel\Types;

use Consistence;
use function strval;

/**
 * Capability protocol types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Protocol extends Consistence\Enum\Enum
{

	public const POWER_STATE = 'powerState';

	public const TOGGLE_STATE = 'toggleState';

	public const BRIGHTNESS = 'brightness';

	public const COLOR_TEMPERATURE = 'colorTemperature';

	public const COLOR_RED = 'red';

	public const COLOR_GREEN = 'green';

	public const COLOR_BLUE = 'blue';

	public const PERCENTAGE = 'percentage';

	public const MOTOR_CONTROL = 'motorControl';

	public const MOTOR_REVERSE = 'motorReverse';

	public const MOTOR_CALIBRATION = 'motorClb';

	public const STARTUP = 'startup';

	public const STREAM_URL = 'streamUrl';

	public const DETECT = 'detect';

	public const HUMIDITY = 'humidity';

	public const TEMPERATURE = 'temperature';

	public const BATTERY = 'battery';

	public const PRESS = 'press';

	public const RSSI = 'rssi';

	public const CONFIGURATION = 'configuration';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
