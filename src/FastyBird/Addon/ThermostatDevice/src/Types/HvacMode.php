<?php declare(strict_types = 1);

/**
 * HvacMode.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Addon\ThermostatDevice\Types;

use Consistence;
use function strval;

/**
 * HVAC modes types
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HvacMode extends Consistence\Enum\Enum
{

	public const OFF = 'off';

	public const HEAT = 'heat';

	public const COOL = 'cool';

	public const AUTO = 'auto';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
