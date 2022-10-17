<?php declare(strict_types = 1);

/**
 * ConnectorPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          0.13.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Types;

use Consistence;
use function strval;

/**
 * Connector property identifier types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IDENTIFIER_CLIENT_MODE = 'mode';

	public const IDENTIFIER_ACCESS_ID = 'access_id';

	public const IDENTIFIER_ACCESS_SECRET = 'access_secret';

	public const IDENTIFIER_UID = 'uid';

	public const IDENTIFIER_OPENAPI_ENDPOINT = 'openapi_endpoint';

	public const IDENTIFIER_OPENPULSAR_ENDPOINT = 'openpulsar_endpoint';

	public const IDENTIFIER_OPENPULSAR_TOPIC = 'openpulsar_topic';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
