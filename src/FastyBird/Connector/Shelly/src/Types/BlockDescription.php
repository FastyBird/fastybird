<?php declare(strict_types = 1);

/**
 * BlockDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           03.01.23
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Block descriptions
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class BlockDescription extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const DESC_RELAY = 'relay';

	public const DESC_ROLLER = 'roller';

	public const DESC_LIGHT = 'light';

	public const DESC_INPUT = 'input';

	public const DESC_EMETER = 'emeter';

	public const DESC_DEVICE = 'device';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
