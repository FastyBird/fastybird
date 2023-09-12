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

	public const ON = 'on';

	public const STATE = 'state';

	public const POSITION = 'pos';

	public const BRIGHTNESS = 'brightness';

	public const CELSIUS = 'celsius';

	public const FAHRENHEIT = 'fahrenheit';

	public const HUMIDITY = 'humidity';

	public const ACTIVE_POWER = 'active_power';

	public const POWER_FACTOR = 'power_factor';

	public const ACTIVE_ENERGY = 'active_energy';

	public const CURRENT = 'current';

	public const VOLTAGE = 'voltage';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
