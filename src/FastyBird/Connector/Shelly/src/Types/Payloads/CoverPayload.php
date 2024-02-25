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

namespace FastyBird\Connector\Shelly\Types\Payloads;

/**
 * Cover component payload values
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum CoverPayload: string implements Payload
{

	case OPEN = 'open';

	case CLOSED = 'closed';

	case OPENING = 'opening';

	case CLOSING = 'closing';

	case STOPPED = 'stopped';

	case CALIBRATING = 'calibrating';

}
