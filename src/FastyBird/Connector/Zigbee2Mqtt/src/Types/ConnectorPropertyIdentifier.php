<?php declare(strict_types = 1);

/**
 * ConnectorPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Connector property name types
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ConnectorPropertyIdentifier: string
{

	case STATE = DevicesTypes\ConnectorPropertyIdentifier::STATE->value;

	case SERVER = DevicesTypes\ConnectorPropertyIdentifier::SERVER->value;

	case PORT = DevicesTypes\ConnectorPropertyIdentifier::PORT->value;

	case SECURED_PORT = DevicesTypes\ConnectorPropertyIdentifier::SECURED_PORT->value;

	case CLIENT_MODE = 'mode';

	case USERNAME = 'username';

	case PASSWORD = 'password';

	case BASE_TOPIC = 'base_topic';

}
