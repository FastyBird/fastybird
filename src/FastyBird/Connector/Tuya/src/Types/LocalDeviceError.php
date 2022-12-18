<?php declare(strict_types = 1);

/**
 * LocalDeviceError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          0.13.0
 *
 * @date           11.12.22
 */

namespace FastyBird\Connector\Tuya\Types;

use Consistence;
use function strval;

/**
 * Local device communication error types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class LocalDeviceError extends Consistence\Enum\Enum
{

	/**
	 * Define codes
	 */
	public const ERR_PAYLOAD = 'payload';

	public const ERR_DEVICE_TYPE = 'device_type';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
