<?php declare(strict_types = 1);

/**
 * ConnectorPropertyIdentifier.php
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
 * Connector property identifier types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ConnectorPropertyIdentifier: string
{

	case CLIENT_MODE = 'mode';

	case ACCESS_ID = 'access_id';

	case ACCESS_SECRET = 'access_secret';

	case UID = 'uid';

	case OPENAPI_ENDPOINT = 'openapi_endpoint';

	case OPENPULSAR_ENDPOINT = 'openpulsar_endpoint';

	case OPENPULSAR_TOPIC = 'openpulsar_topic';

}
