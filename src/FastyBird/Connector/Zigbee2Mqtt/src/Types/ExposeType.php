<?php declare(strict_types = 1);

/**
 * ExposeType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Expose data types
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ExposeType: string
{

	case BINARY = 'binary';

	case NUMERIC = 'numeric';

	case ENUM = 'enum';

	case TEXT = 'text';

	case COMPOSITE = 'composite';

	case LIST = 'list';

	case LIGHT = 'light';

	case SWITCH = 'switch';

	case FAN = 'fan';

	case COVER = 'cover';

	case LOCK = 'lock';

	case CLIMATE = 'climate';

}
