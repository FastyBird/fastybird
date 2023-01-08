<?php declare(strict_types = 1);

/**
 * InputPayload.php
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
 * Input component payload values
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class InputPayload extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const PAYLOAD_PRESS = 'btn_down';

	public const PAYLOAD_RELEASE = 'btn_up';

	public const PAYLOAD_SINGLE_PUSH = 'single_push';

	public const PAYLOAD_DOUBLE_PUSH = 'double_push';

	public const PAYLOAD_LONG_PUSH = 'long_push';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
