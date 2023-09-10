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

	public const PRESS = 'btn_down';

	public const RELEASE = 'btn_up';

	public const SINGLE_PUSH = 'single_push';

	public const DOUBLE_PUSH = 'double_push';

	public const TRIPLE_PUSH = 'triple_push';

	public const LONG_PUSH = 'long_push';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
