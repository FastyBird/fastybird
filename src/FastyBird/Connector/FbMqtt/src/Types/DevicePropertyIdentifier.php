<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 * @since          0.25.0
 *
 * @date           23.07.22
 */

namespace FastyBird\Connector\FbMqtt\Types;

use Consistence;
use FastyBird\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device property identifier types
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IDENTIFIER_STATE = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE;

	public const IDENTIFIER_IP_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS;

	public const IDENTIFIER_STATUS_LED = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATUS_LED;

	public const IDENTIFIER_UPTIME = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_UPTIME;

	public const IDENTIFIER_FREE_HEAP = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_FREE_HEAP;

	public const IDENTIFIER_CPU_LOAD = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_CPU_LOAD;

	public const IDENTIFIER_VCC = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_VCC;

	public const IDENTIFIER_RSSI = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_RSSI;

	public const IDENTIFIER_USERNAME = 'username';

	public const IDENTIFIER_PASSWORD = 'password';

	public const IDENTIFIER_AUTH_ENABLED = 'auth_enabled';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
