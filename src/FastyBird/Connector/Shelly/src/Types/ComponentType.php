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

	public const SWITCH = 'switch';

	public const COVER = 'cover';

	public const LIGHT = 'light';

	public const INPUT = 'input';

	public const TEMPERATURE = 'temperature';

	public const HUMIDITY = 'humidity';

	public const ETHERNET = 'ethernet';

	public const WIFI = 'wifi';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
