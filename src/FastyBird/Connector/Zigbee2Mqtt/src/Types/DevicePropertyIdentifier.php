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
 * @date           25.12.23
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
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	public const STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	public const BASE_TOPIC = 'base_topic';

	public const MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	public const MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER->value;

	public const VERSION = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_VERSION->value;

	public const COMMIT = 'commit';

	public const IEEE_ADDRESS = DevicesTypes\DevicePropertyIdentifier::ADDRESS->value;

	public const TYPE = 'type';

	public const SUPPORTED = 'supported';

	public const DISABLED = 'disabled';

	public const FRIENDLY_NAME = 'friendly_name';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
