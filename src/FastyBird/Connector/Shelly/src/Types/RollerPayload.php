<?php declare(strict_types = 1);

/**
 * RollerPayload.php
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
 * Roller payload values
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RollerPayload extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const PAYLOAD_STOP = 'stop';

	public const PAYLOAD_OPEN = 'open';

	public const PAYLOAD_CLOSE = 'close';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
