<?php declare(strict_types = 1);

/**
 * DeviceProtocolVersion.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          0.13.0
 *
 * @date           25.08.22
 */

namespace FastyBird\TuyaConnector\Types;

use Consistence;
use function strval;

/**
 * Device protocol version types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceProtocolVersion extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const VERSION_V31 = '3.1';

	public const VERSION_V33 = '3.3';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
