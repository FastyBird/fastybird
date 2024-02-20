<?php declare(strict_types = 1);

/**
 * BridgeMessageType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Bridge message types
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum BridgeMessageType: string
{

	case INFO = 'info';

	case STATE = 'state';

	case LOGGING = 'logging';

	case DEVICES = 'devices';

	case GROUPS = 'groups';

	case EVENT = 'event';

	case EXTENSIONS = 'extensions';

}
