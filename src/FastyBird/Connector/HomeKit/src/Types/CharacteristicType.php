<?php declare(strict_types = 1);

/**
 * CharacteristicType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           12.04.23
 */

namespace FastyBird\Connector\HomeKit\Types;

/**
 * HAP service characteristic type types
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum CharacteristicType: string
{

	case BRIGHTNESS = 'Brightness';

	case HUE = 'Hue';

	case SATURATION = 'Saturation';

	case NAME = 'Name';

	case ON = 'On';

	case COLOR_RED = 'ColorRed';

	case COLOR_GREEN = 'ColorGreen';

	case COLOR_BLUE = 'ColorBlue';

	case COLOR_WHITE = 'ColorWhite';

	case CURRENT_HEATING_COOLING_STATE = 'CurrentHeatingCoolingState';

	case TARGET_HEATING_COOLING_STATE = 'TargetHeatingCoolingState';

	case CURRENT_TEMPERATURE = 'CurrentTemperature';

	case TARGET_TEMPERATURE = 'TargetTemperature';

	case TEMPERATURE_DISPLAY_UNITS = 'TemperatureDisplayUnits';

	case CURRENT_RELATIVE_HUMIDITY = 'CurrentRelativeHumidity';

	case TARGET_RELATIVE_HUMIDITY = 'TargetRelativeHumidity';

	case COOLING_THRESHOLD_TEMPERATURE = 'CoolingThresholdTemperature';

	case HEATING_THRESHOLD_TEMPERATURE = 'HeatingThresholdTemperature';

}
