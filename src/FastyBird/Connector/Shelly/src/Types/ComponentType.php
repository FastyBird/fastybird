<?php declare(strict_types = 1);

/**
 * ComponentType.php
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
 * Generation 2 devices component types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ComponentType extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const TYPE_SWITCH = 'switch';

	public const TYPE_COVER = 'cover';

	public const TYPE_LIGHT = 'light';

	public const TYPE_INPUT = 'input';

	public const TYPE_TEMPERATURE = 'temperature';

	public const TYPE_HUMIDITY = 'humidity';

	public const TYPE_ETHERNET = 'ethernet';

	public const TYPE_WIFI = 'wifi';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
