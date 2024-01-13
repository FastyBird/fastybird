<?php declare(strict_types = 1);

/**
 * ChannelPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Addon\ThermostatDevice\Types;

use Consistence;
use function strval;

/**
 * Channel property identifier types
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertyIdentifier extends Consistence\Enum\Enum
{

	public const OPENING_SENSOR = 'opening_sensor';

	public const HEATER_ACTOR = 'heater_actor';

	public const COOLER_ACTOR = 'cooler_actor';

	public const TARGET_SENSOR = 'target_sensor';

	public const FLOOR_SENSOR = 'floor_sensor';

	public const MAXIMUM_FLOOR_TEMPERATURE = 'max_floor_temperature';

	public const ACTUAL_FLOOR_TEMPERATURE = 'actual_floor_temperature';

	public const TARGET_TEMPERATURE = 'target_temperature';

	public const ACTUAL_TEMPERATURE = 'actual_temperature';

	public const COOLING_THRESHOLD_TEMPERATURE = 'cooling_threshold_temperature';

	public const HEATING_THRESHOLD_TEMPERATURE = 'heating_threshold_temperature';

	public const LOW_TARGET_TEMPERATURE_TOLERANCE = 'low_target_temperature_tolerance';

	public const HIGH_TARGET_TEMPERATURE_TOLERANCE = 'high_target_temperature_tolerance';

	public const MINIMUM_CYCLE_DURATION = 'min_cycle_duration';

	public const PRESET_MODE = 'preset_mode';

	public const HVAC_MODE = 'hvac_mode';

	public const HVAC_STATE = 'hvac_state';

	public const UNIT = 'unit';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
