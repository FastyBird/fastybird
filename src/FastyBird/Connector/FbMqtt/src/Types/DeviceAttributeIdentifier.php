<?php declare(strict_types = 1);

/**
 * DeviceAttributeIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           23.07.22
 */

namespace FastyBird\Connector\FbMqtt\Types;

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device attribute identifier types
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceAttributeIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IDENTIFIER_HARDWARE_MAC_ADDRESS = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

	public const IDENTIFIER_HARDWARE_MANUFACTURER = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER;

	public const IDENTIFIER_HARDWARE_MODEL = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public const IDENTIFIER_HARDWARE_VERSION = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_VERSION;

	public const IDENTIFIER_FIRMWARE_MANUFACTURER = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_MANUFACTURER;

	public const IDENTIFIER_FIRMWARE_NAME = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_NAME;

	public const IDENTIFIER_FIRMWARE_VERSION = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_VERSION;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
