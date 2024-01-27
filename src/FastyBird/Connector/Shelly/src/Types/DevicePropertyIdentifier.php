<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           19.07.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device property identifiers
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	public const IP_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IP_ADDRESS;

	public const STATE = MetadataTypes\DevicePropertyIdentifier::STATE;

	public const MODEL = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MODEL;

	public const MAC_ADDRESS = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS;

	public const FIRMWARE_VERSION = MetadataTypes\DevicePropertyIdentifier::FIRMWARE_VERSION;

	public const DOMAIN = 'domain';

	public const USERNAME = 'username';

	public const PASSWORD = 'password';

	public const AUTH_ENABLED = 'auth_enabled';

	public const GENERATION = 'generation';

	public const STATE_READING_DELAY = 'state_reading_delay';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
