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

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Connector property name types
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IDENTIFIER_SERVER = MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_SERVER;

	public const IDENTIFIER_PORT = MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_PORT;

	public const IDENTIFIER_SECURED_PORT = MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_SECURED_PORT;

	public const IDENTIFIER_USERNAME = 'username';

	public const IDENTIFIER_PASSWORD = 'password';

	public const IDENTIFIER_PROTOCOL_VERSION = 'protocol';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
