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

	public const ALARM = 'A';

	public const BATTERY_LEVEL = 'B';

	public const CONCENTRATION = 'C';

	public const ENERGY = 'E';

	public const EVENT = 'EV';

	public const EVENT_COUNTER = 'EVC';

	public const HUMIDITY = 'H';

	public const CURRENT = 'I';

	public const LUMINOSITY = 'L';

	public const POWER = 'P';

	public const STATUS = 'S';

	public const TEMPERATURE = 'T';

	public const VOLTAGE = 'V';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
