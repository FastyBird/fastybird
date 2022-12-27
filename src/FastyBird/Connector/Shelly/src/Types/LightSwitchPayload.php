<?php declare(strict_types = 1);

/**
 * LightSwitchPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Light switch payload values
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class LightSwitchPayload extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const PAYLOAD_ON = 'on';

	public const PAYLOAD_OFF = 'off';

	public const PAYLOAD_TOGGLE = 'toggle';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
