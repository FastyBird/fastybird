<?php declare(strict_types = 1);

/**
 * ChannelType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           26.12.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Generation 2 devices component channels types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelType extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const TYPE_SWITCH = 'switch';

	public const TYPE_COVER = 'cover';

	public const TYPE_BRIGHTNESS = 'brightness';

	public const TYPE_BINARY_INPUT = 'analog_input';

	public const TYPE_ANALOG_INPUT = 'binary_input';

	public const TYPE_BUTTON = 'button';

	public const TYPE_TEMPERATURE = 'temperature';

	public const TYPE_HUMIDITY = 'humidity';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
