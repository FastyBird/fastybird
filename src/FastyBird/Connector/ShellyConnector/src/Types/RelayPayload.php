<?php declare(strict_types = 1);

/**
 * RelayPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          0.37.0
 *
 * @date           17.07.22
 */

namespace FastyBird\ShellyConnector\Types;

use Consistence;
use function strval;

/**
 * Relay payload value types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RelayPayload extends Consistence\Enum\Enum
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
