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

use Consistence;
use function strval;

/**
 * HAP service characteristic type types
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class CharacteristicType extends Consistence\Enum\Enum
{

	/**
	 * Define statuses
	 */
	public const BRIGHTNESS = 'Brightness';

	public const HUE = 'Hue';

	public const SATURATION = 'Saturation';

	public const NAME = 'Name';

	public const ON = 'On';

	public const COLOR_RED = 'ColorRed';

	public const COLOR_GREEN = 'ColorGreen';

	public const COLOR_BLUE = 'ColorBlue';

	public const COLOR_WHITE = 'ColorWhite';

	public const CURRENT_HEATING_COOLING_STATE = 'CurrentHeatingCoolingState';

	public const TARGET_HEATING_COOLING_STATE = 'TargetHeatingCoolingState';

	public const CURRENT_TEMPERATURE = 'CurrentTemperature';

	public const TARGET_TEMPERATURE = 'TargetTemperature';

	public const TEMPERATURE_DISPLAY_UNITS = 'TemperatureDisplayUnits';

	public const CURRENT_RELATIVE_HUMIDITY = 'CurrentRelativeHumidity';

	public const TARGET_RELATIVE_HUMIDITY = 'TargetRelativeHumidity';

	public const COOLING_THRESHOLD_TEMPERATURE = 'CoolingThresholdTemperature';

	public const HEATING_THRESHOLD_TEMPERATURE = 'HeatingThresholdTemperature';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
