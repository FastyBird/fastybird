<?php declare(strict_types = 1);

/**
 * ConnectorPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          0.37.0
 *
 * @date           19.07.22
 */

namespace FastyBird\ShellyConnector\Types;

use Consistence;
use function strval;

/**
 * Connector property identifier types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IDENTIFIER_CLIENT_VERSION = 'version';

	public const IDENTIFIER_CLIENT_MODE = 'mode';

	public const IDENTIFIER_CLOUD_AUTH_KEY = 'cloud_auth_key';

	public const IDENTIFIER_CLOUD_SERVER = 'cloud_server';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
