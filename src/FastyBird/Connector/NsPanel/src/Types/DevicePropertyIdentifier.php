<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Types;

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device property identifier types
 *
 * @package        FastyBird:NsPanelConnector!
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

	public const IDENTIFIER_DOMAIN = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_DOMAIN;

	public const IDENTIFIER_MANUFACTURER = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER;

	public const IDENTIFIER_MODEL = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public const IDENTIFIER_MAC_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

	public const IDENTIFIER_FIRMWARE_VERSION = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION;

	public const IDENTIFIER_ACCESS_TOKEN = 'access_token';

	public const IDENTIFIER_CATEGORY = 'category';

	public const IDENTIFIER_GATEWAY_IDENTIFIER = 'gateway_identifier';

	public const IDENTIFIER_STATUS_READING_DELAY = 'status_reading_delay';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
