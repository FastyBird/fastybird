<?php declare(strict_types = 1);

/**
 * ConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.03.18
 */

namespace FastyBird\Module\Devices\Types;

/**
 * Connection state types
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ConnectionState: string
{

	case CONNECTED = 'connected';

	case DISCONNECTED = 'disconnected';

	case INIT = 'init';

	case READY = 'ready';

	case RUNNING = 'running';

	case SLEEPING = 'sleeping';

	case STOPPED = 'stopped';

	case LOST = 'lost';

	case ALERT = 'alert';

	case UNKNOWN = 'unknown';

}
