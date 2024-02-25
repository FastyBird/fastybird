<?php declare(strict_types = 1);

/**
 * DeviceType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Device types
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum DeviceType: string
{

	case END_DEVICE = 'EndDevice';

	case COORDINATOR = 'Coordinator';

	case ROUTER = 'Router';

	case UNKNOWN = 'Unknown';

}
