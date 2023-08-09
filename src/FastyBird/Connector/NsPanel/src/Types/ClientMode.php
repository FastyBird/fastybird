<?php declare(strict_types = 1);

/**
 * ClientMode.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           05.08.23
 */

namespace FastyBird\Connector\NsPanel\Types;

use Consistence;
use function strval;

/**
 * Connector client modes
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientMode extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const GATEWAY = 'gateway';

	public const DEVICE = 'device';

	public const BOTH = 'both';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
