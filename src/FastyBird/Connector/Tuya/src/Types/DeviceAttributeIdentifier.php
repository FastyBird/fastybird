<?php declare(strict_types = 1);

/**
 * DeviceAttributeIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          0.13.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Types;

use Consistence;
use FastyBird\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device attribute identifier types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceAttributeIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IDENTIFIER_HARDWARE_MODEL = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public const IDENTIFIER_HARDWARE_MAC_ADDRESS = MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

	public const IDENTIFIER_SERIAL_NUMBER = 'serial_number';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
