<?php declare(strict_types = 1);

/**
 * SensorDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Sensor descriptions
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SensorDescription extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const DESC_MODE = 'mode';

	public const DESC_OUTPUT = 'output';

	public const DESC_ROLLER = 'roller';

	public const DESC_ROLLER_POSITION = 'rollerPos';

	public const DESC_ROLLER_STOP_REASON = 'rollerStopReason';

	public const DESC_RED = 'red';

	public const DESC_GREEN = 'green';

	public const DESC_BLUE = 'blue';

	public const DESC_WHITE = 'white';

	public const DESC_WHITE_LEVEL = 'whiteLevel';

	public const DESC_GAIN = 'gain';

	public const DESC_COLOR_TEMP = 'colorTemp';

	public const DESC_BRIGHTNESS = 'brightness';

	public const DESC_EFFECT = 'effect';

	public const DESC_OVERPOWER = 'overpower';

	public const DESC_OVERTEMPERATURE = 'overtemp';

	public const DESC_INPUT = 'input';

	public const DESC_INPUT_EVENT = 'inputEvent';

	public const DESC_INPUT_EVENT_COUNT = 'inputEventCnt';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
