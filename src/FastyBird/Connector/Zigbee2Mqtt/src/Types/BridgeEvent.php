<?php declare(strict_types = 1);

/**
 * BridgeEvent.php
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
 * Bridge events
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum BridgeEvent: string
{

	case DEVICE_JOINED = 'device_joined';

	case DEVICE_INTERVIEW = 'device_interview';

	case DEVICE_LEAVE = 'device_leave';

	case DEVICE_ANNOUNCE = 'device_announce';

}
