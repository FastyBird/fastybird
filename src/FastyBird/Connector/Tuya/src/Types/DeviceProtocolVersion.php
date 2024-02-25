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

/**
 * Device protocol version types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum DeviceProtocolVersion: string
{

	case V31 = '3.1';

	case V32 = '3.2';

	case V32_PLUS = '3.2+';

	case V33 = '3.3';

	case V34 = '3.4';

}
