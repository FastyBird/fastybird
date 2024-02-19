<?php declare(strict_types = 1);

/**
 * LocalDeviceError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           11.12.22
 */

namespace FastyBird\Connector\Tuya\Types;

/**
 * Local device communication error types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum LocalDeviceError: string
{

	case PAYLOAD = 'payload';

	case DEVICE_TYPE = 'device_type';

}
