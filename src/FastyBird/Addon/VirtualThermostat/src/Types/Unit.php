<?php declare(strict_types = 1);

/**
 * Unit.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           13.01.24
 */

namespace FastyBird\Addon\VirtualThermostat\Types;

use Consistence;
use function strval;

/**
 * Temperature unit types
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Unit extends Consistence\Enum\Enum
{

	public const CELSIUS = '°C';

	public const FAHRENHEIT = '°F';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
