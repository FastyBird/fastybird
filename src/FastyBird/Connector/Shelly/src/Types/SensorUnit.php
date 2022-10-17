<?php declare(strict_types = 1);

/**
 * SensorUnit.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          0.37.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Sensor unit types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SensorUnit extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const UNIT_WATTS = 'W';

	public const UNIT_WATT_MINUTES = 'Wmin';

	public const UNIT_WATT_HOURS = 'Wh';

	public const UNIT_VOLTS = 'V';

	public const UNIT_AMPERES = 'A';

	public const UNIT_CELSIUS = 'C';

	public const UNIT_FAHRENHEIT = 'F';

	public const UNIT_KELVIN = 'K';

	public const UNIT_DEGREES = 'deg';

	public const UNIT_LUX = 'lux';

	public const UNIT_PARTS_PER_MILLION = 'ppm';

	public const UNIT_SECONDS = 's';

	public const UNIT_PERCENT = 'pct';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
