<?php declare(strict_types = 1);

/**
 * ConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.03.18
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Connection state types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectionState extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const CONNECTED = 'connected';

	public const DISCONNECTED = 'disconnected';

	public const INIT = 'init';

	public const READY = 'ready';

	public const RUNNING = 'running';

	public const SLEEPING = 'sleeping';

	public const STOPPED = 'stopped';

	public const LOST = 'lost';

	public const ALERT = 'alert';

	public const UNKNOWN = 'unknown';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
