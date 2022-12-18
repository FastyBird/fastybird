<?php declare(strict_types = 1);

/**
 * LocalDeviceType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          0.13.0
 *
 * @date           10.12.22
 */

namespace FastyBird\Connector\Tuya\Types;

use Consistence;
use function strval;

/**
 * Local device types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class LocalDeviceType extends Consistence\Enum\Enum
{

	/**
	 * Define codes
	 */
	public const DEFAULT = 'default';

	/**
	 * Special Case Device with 22 character ID - Some of these devices
	 * Require the 0d command as the DP_QUERY status request and the list of
	 * dps requested payload
	 */
	public const DEVICE_22 = 'device22';

	public const DEVICE_V34 = 'device_v3.4';

	public const ZIGBEE = 'zigbee';

	public const GATEWAY = 'gateway';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
