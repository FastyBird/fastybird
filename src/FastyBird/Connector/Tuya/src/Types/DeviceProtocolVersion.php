<?php declare(strict_types = 1);

/**
 * DeviceProtocolVersion.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Types;

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
	public const V31 = '3.1';

	public const V32 = '3.2';

	public const V32_PLUS = '3.2+';

	public const V33 = '3.3';

	public const V34 = '3.4';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
