<?php declare(strict_types = 1);

/**
 * ComponentAttributeType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.12.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Generation 2 devices component attribute types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ComponentAttributeType extends Consistence\Enum\Enum
{

	public const OUTPUT = 'output';

	public const HUMIDITY = 'humidity';

	public const ACTIVE_POWER = 'active_power';

	public const POWER_FACTOR = 'power_factor';

	public const ACTIVE_ENERGY_TOTAL = 'active_energy_total';

	public const CURRENT = 'current';

	public const VOLTAGE = 'voltage';

	public const TEMPERATURE_CELSIUS = 'temperature_celsius';

	public const TEMPERATURE_FAHRENHEIT = 'temperature_fahrenheit';

	public const STATE = 'state';

	public const CURRENT_POSITION = 'current_position';

	public const TARGET_POSITION = 'target_position';

	public const BRIGHTNESS = 'brightness';

	public const SWITCH = 'switch';

	public const BUTTON = 'button';

	public const ANALOG = 'analog';

	public const CELSIUS = 'celsius';

	public const FAHRENHEIT = 'fahrenheit';

	public const RELATIVE_HUMIDITY = 'relative_humidity';

	public const X_VOLTAGE = 'xvoltage';

	public const RUNNING = 'running';

	public const BATTERY_VOLTAGE = 'battery_voltage';

	public const BATTER_PERCENT = 'battery_percent';

	public const EXTERNAL_PRESENT = 'external_present';

	public const ALARM = 'alarm';

	public const MUTE = 'mute';

	public const ON = 'on';

	public const POSITION = 'pos';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
