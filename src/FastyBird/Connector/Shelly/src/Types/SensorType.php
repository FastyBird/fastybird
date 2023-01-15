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

use Consistence;
use function strval;

/**
 * Sensor types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SensorType extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const TYPE_ALARM = 'A';

	public const TYPE_BATTERY_LEVEL = 'B';

	public const TYPE_CONCENTRATION = 'C';

	public const TYPE_ENERGY = 'E';

	public const TYPE_EVENT = 'EV';

	public const TYPE_EVENT_COUNTER = 'EVC';

	public const TYPE_HUMIDITY = 'H';

	public const TYPE_CURRENT = 'I';

	public const TYPE_LUMINOSITY = 'L';

	public const TYPE_POWER = 'P';

	public const TYPE_STATUS = 'S';

	public const TYPE_TEMPERATURE = 'T';

	public const TYPE_VOLTAGE = 'V';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
