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

	public const CLIENT_MODE = 'mode';

	public const ACCESS_ID = 'access_id';

	public const ACCESS_SECRET = 'access_secret';

	public const UID = 'uid';

	public const OPENAPI_ENDPOINT = 'openapi_endpoint';

	public const OPENPULSAR_ENDPOINT = 'openpulsar_endpoint';

	public const OPENPULSAR_TOPIC = 'openpulsar_topic';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
