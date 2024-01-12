<?php declare(strict_types = 1);

/**
 * ChannelIdentifier.php
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
 * Channel identifier types
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelIdentifier extends Consistence\Enum\Enum
{

	public const CONFIGURATION = 'configuration';

	public const PRESET_AWAY = 'preset_away';

	public const PRESET_ECO = 'preset_eco';

	public const PRESET_HOME = 'preset_home';

	public const PRESET_COMFORT = 'preset_comfort';

	public const PRESET_SLEEP = 'preset_sleep';

	public const PRESET_ANTI_FREEZE = 'preset_anti_freeze';

	public const SENSORS = 'sensors';

	public const ACTORS = 'actors';

	public const OPENINGS = 'openings';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
