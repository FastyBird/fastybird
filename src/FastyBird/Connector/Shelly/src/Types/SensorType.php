<?php declare(strict_types = 1);

/**
 * SensorType.php
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
 * Sensor types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum SensorType: string
{

	case ALARM = 'A';

	case BATTERY_LEVEL = 'B';

	case CONCENTRATION = 'C';

	case ENERGY = 'E';

	case EVENT = 'EV';

	case EVENT_COUNTER = 'EVC';

	case HUMIDITY = 'H';

	case CURRENT = 'I';

	case LUMINOSITY = 'L';

	case POWER = 'P';

	case STATUS = 'S';

	case TEMPERATURE = 'T';

	case VOLTAGE = 'V';

}
