<?php declare(strict_types = 1);

/**
 * ConnectorPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           10.02.22
 */

namespace FastyBird\Connector\FbMqtt\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Connector property name types
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ConnectorPropertyIdentifier: string
{

	case SERVER = DevicesTypes\ConnectorPropertyIdentifier::SERVER->value;

	case PORT = DevicesTypes\ConnectorPropertyIdentifier::PORT->value;

	case SECURED_PORT = DevicesTypes\ConnectorPropertyIdentifier::SECURED_PORT->value;

	case USERNAME = 'username';

	case PASSWORD = 'password';

	case PROTOCOL_VERSION = 'protocol';

}
