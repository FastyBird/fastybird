<?php declare(strict_types = 1);

/**
 * CoverPayload.php
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
 * Cover component payload values
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class CoverPayload extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const PAYLOAD_OPEN = 'open';

	public const PAYLOAD_CLOSED = 'closed';

	public const PAYLOAD_OPENING = 'opening';

	public const PAYLOAD_CLOSING = 'closing';

	public const PAYLOAD_STOPPED = 'stopped';

	public const PAYLOAD_CALIBRATING = 'calibrating';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
