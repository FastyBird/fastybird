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

	/**
	 * Define device states
	 */
	public const ATTRIBUTE_ON = 'on';

	public const ATTRIBUTE_STATE = 'state';

	public const ATTRIBUTE_POSITION = 'pos';

	public const ATTRIBUTE_BRIGHTNESS = 'brightness';

	public const ATTRIBUTE_CELSIUS = 'celsius';

	public const ATTRIBUTE_FAHRENHEIT = 'fahrenheit';

	public const ATTRIBUTE_HUMIDITY = 'humidity';

	public const ATTRIBUTE_ACTIVE_POWER = 'active_power';

	public const ATTRIBUTE_POWER_FACTOR = 'power_factor';

	public const ATTRIBUTE_ACTIVE_ENERGY = 'active_energy';

	public const ATTRIBUTE_CURRENT = 'current';

	public const ATTRIBUTE_VOLTAGE = 'voltage';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
