<?php declare(strict_types = 1);

/**
 * ExposeDataType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           02.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Expose data type types
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ExposeDataType: string
{

	case SINGLE = 'single';

	case COMPOSITE = 'composite';

}
