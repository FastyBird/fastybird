<?php declare(strict_types = 1);

/**
 * DeviceAttributeIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           22.07.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device attribute identifiers
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceAttributeIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IDENTIFIER_FIRMWARE_VERSION = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_VERSION;

	public const IDENTIFIER_MAC_ADDRESS = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

	public const IDENTIFIER_HARDWARE_MODEL = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
