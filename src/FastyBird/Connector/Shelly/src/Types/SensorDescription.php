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

	public const MODE = 'mode';

	public const OUTPUT = 'output';

	public const ROLLER = 'roller';

	public const ROLLER_POSITION = 'rollerPos';

	public const ROLLER_STOP_REASON = 'rollerStopReason';

	public const ROLLER_POWER = 'rollerPower';

	public const ROLLER_ENERGY = 'rollerEnergy';

	public const RED = 'red';

	public const GREEN = 'green';

	public const BLUE = 'blue';

	public const WHITE = 'white';

	public const WHITE_LEVEL = 'whiteLevel';

	public const GAIN = 'gain';

	public const COLOR_TEMP = 'colorTemp';

	public const BRIGHTNESS = 'brightness';

	public const EFFECT = 'effect';

	public const OVERPOWER = 'overpower';

	public const OVERPOWER_VALUE = 'overpowerValue';

	public const OVERTEMPERATURE = 'overtemp';

	public const INPUT = 'input';

	public const INPUT_EVENT = 'inputEvent';

	public const INPUT_EVENT_COUNT = 'inputEventCnt';

	public const ACTIVE_POWER = 'power';

	public const REACTIVE_POWER = 'reactive';

	public const ENERGY = 'energy';

	public const ENERGY_RETURNED = 'energyReturned';

	public const VOLTAGE = 'voltage';

	public const CURRENT = 'current';

	public const POWER_FACTOR = 'powerFactor';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
