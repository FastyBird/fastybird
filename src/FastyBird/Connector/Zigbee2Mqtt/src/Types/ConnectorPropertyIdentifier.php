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

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Connector property name types
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertyIdentifier extends Consistence\Enum\Enum
{

	public const STATE = MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE;

	public const CLIENT_MODE = 'mode';

	public const SERVER = MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_SERVER;

	public const PORT = MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_PORT;

	public const SECURED_PORT = MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_SECURED_PORT;

	public const USERNAME = 'username';

	public const PASSWORD = 'password';

	public const BASE_TOPIC = 'base_topic';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
