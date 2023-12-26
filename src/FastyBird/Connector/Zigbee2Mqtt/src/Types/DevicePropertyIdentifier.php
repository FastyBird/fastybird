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
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	public const STATE = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE;

	public const BASE_TOPIC = 'base_topic';

	public const FIRMWARE_VERSION = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION;

	public const FIRMWARE_COMMIT = 'commit';

	public const IEEE_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_ADDRESS;

	public const TYPE = 'type';

	public const SUPPORTED = 'supported';

	public const DISABLED = 'disabled';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
