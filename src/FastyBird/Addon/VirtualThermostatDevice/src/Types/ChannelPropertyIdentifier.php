<?php declare(strict_types = 1);

/**
 * ChannelPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Addon\VirtualThermostatDevice\Types;

use Consistence;
use function strval;

/**
 * Channel property identifier types
 *
 * @package        FastyBird:VirtualThermostatDeviceAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertyIdentifier extends Consistence\Enum\Enum
{

	// ACTORS & SENSORS
	public const HEATER_ACTOR = 'heater_actor';

	public const COOLER_ACTOR = 'cooler_actor';

	public const ROOM_TEMPERATURE_SENSOR = 'room_temperature_sensor';

	public const FLOOR_TEMPERATURE_SENSOR = 'floor_temperature_sensor';

	public const ROOM_HUMIDITY_SENSOR = 'room_humidity_sensor';

	public const OPENING_SENSOR = 'opening_sensor';

	// CONFIGURATION
	public const MAXIMUM_FLOOR_TEMPERATURE = 'max_floor_temperature';

	public const LOW_TARGET_TEMPERATURE_TOLERANCE = 'low_target_temperature_tolerance';

	public const HIGH_TARGET_TEMPERATURE_TOLERANCE = 'high_target_temperature_tolerance';

	public const MINIMUM_CYCLE_DURATION = 'min_cycle_duration';

	public const UNIT = 'unit';

	// PRESET
	public const TARGET_TEMPERATURE = 'target_temperature';

	public const COOLING_THRESHOLD_TEMPERATURE = 'cooling_threshold_temperature';

	public const HEATING_THRESHOLD_TEMPERATURE = 'heating_threshold_temperature';

	// STATE
	public const CURRENT_FLOOR_TEMPERATURE = 'current_floor_temperature';

	public const CURRENT_ROOM_TEMPERATURE = 'current_room_temperature';

	public const CURRENT_ROOM_HUMIDITY = 'current_room_humidity';

	public const CURRENT_OPENINGS_STATE = 'current_openings_state';

	public const FLOOR_OVERHEATING = 'floor_overheating';

	public const PRESET_MODE = 'preset_mode';

	public const HVAC_MODE = 'hvac_mode';

	public const HVAC_STATE = 'hvac_state';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
