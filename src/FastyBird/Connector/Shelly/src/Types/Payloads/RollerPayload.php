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

namespace FastyBird\Connector\Shelly\Types\Payloads;

/**
 * Roller payload values
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum RollerPayload: string implements Payload
{

	case STOP = 'stop';

	case OPEN = 'open';

	case CLOSE = 'close';

}
