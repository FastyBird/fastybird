<?php declare(strict_types = 1);

/**
 * HvacState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           22.10.23
 */

namespace FastyBird\Addon\ThermostatDevice\Types;

use Consistence;
use function strval;

/**
 * HVAC state types
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HvacState extends Consistence\Enum\Enum
{

	public const INACTIVE = 'inactive';

	public const OFF = 'off';

	public const COOLING = 'cooling';

	public const HEATING = 'heating';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
