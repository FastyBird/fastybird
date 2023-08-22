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

	public const OPEN = 'open';

	public const CLOSED = 'closed';

	public const OPENING = 'opening';

	public const CLOSING = 'closing';

	public const STOPPED = 'stopped';

	public const CALIBRATING = 'calibrating';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
