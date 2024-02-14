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
use FastyBird\Module\Devices\Types as DevicesTypes;
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
	public const SERVER = DevicesTypes\ConnectorPropertyIdentifier::SERVER->value;

	public const PORT = DevicesTypes\ConnectorPropertyIdentifier::PORT->value;

	public const SECURED_PORT = DevicesTypes\ConnectorPropertyIdentifier::SECURED_PORT->value;

	public const USERNAME = 'username';

	public const PASSWORD = 'password';

	public const PROTOCOL_VERSION = 'protocol';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
