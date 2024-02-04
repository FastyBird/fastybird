<?php declare(strict_types = 1);

/**
 * OpeningStatePayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           03.02.24
 */

namespace FastyBird\Addon\VirtualThermostat\Types;

use Consistence;
use function strval;

/**
 * Opening state types
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class OpeningStatePayload extends Consistence\Enum\Enum
{

	public const OPENED = 'opened';

	public const CLOSED = 'closed';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
