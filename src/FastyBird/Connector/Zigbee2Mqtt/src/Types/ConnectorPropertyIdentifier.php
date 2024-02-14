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
use FastyBird\Module\Devices\Types as DevicesTypes;
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

	public const STATE = DevicesTypes\ConnectorPropertyIdentifier::STATE->value;

	public const SERVER = DevicesTypes\ConnectorPropertyIdentifier::SERVER->value;

	public const PORT = DevicesTypes\ConnectorPropertyIdentifier::PORT->value;

	public const SECURED_PORT = DevicesTypes\ConnectorPropertyIdentifier::SECURED_PORT->value;

	public const CLIENT_MODE = 'mode';

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
