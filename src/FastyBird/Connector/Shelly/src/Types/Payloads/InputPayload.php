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

namespace FastyBird\Connector\Shelly\Types\Payloads;

/**
 * Input component payload values
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum InputPayload: string implements Payload
{

	case PRESS = 'btn_down';

	case RELEASE = 'btn_up';

	case SINGLE_PUSH = 'single_push';

	case DOUBLE_PUSH = 'double_push';

	case TRIPLE_PUSH = 'triple_push';

	case LONG_PUSH = 'long_push';

}
