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

/**
 * Sensor descriptions
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum SensorDescription: string
{

	case MODE = 'mode';

	case OUTPUT = 'output';

	case ROLLER = 'roller';

	case ROLLER_POSITION = 'rollerPos';

	case ROLLER_STOP_REASON = 'rollerStopReason';

	case ROLLER_POWER = 'rollerPower';

	case ROLLER_ENERGY = 'rollerEnergy';

	case RED = 'red';

	case GREEN = 'green';

	case BLUE = 'blue';

	case WHITE = 'white';

	case WHITE_LEVEL = 'whiteLevel';

	case GAIN = 'gain';

	case COLOR_TEMP = 'colorTemp';

	case BRIGHTNESS = 'brightness';

	case EFFECT = 'effect';

	case OVERPOWER = 'overpower';

	case OVERPOWER_VALUE = 'overpowerValue';

	case OVERTEMPERATURE = 'overtemp';

	case INPUT = 'input';

	case INPUT_EVENT = 'inputEvent';

	case INPUT_EVENT_COUNT = 'inputEventCnt';

	case ACTIVE_POWER = 'power';

	case REACTIVE_POWER = 'reactive';

	case ENERGY = 'energy';

	case ENERGY_RETURNED = 'energyReturned';

	case VOLTAGE = 'voltage';

	case CURRENT = 'current';

	case POWER_FACTOR = 'powerFactor';

}
