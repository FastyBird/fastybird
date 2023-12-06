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
	public const SERVER = MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_SERVER;

	public const PORT = MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_PORT;

	public const SECURED_PORT = MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_SECURED_PORT;

	public const USERNAME = 'username';

	public const PASSWORD = 'password';

	public const PROTOCOL_VERSION = 'protocol';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
