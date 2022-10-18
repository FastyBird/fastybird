<?php declare(strict_types = 1);

/**
 * DeviceAttributeIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          0.54.0
 *
 * @date           21.04.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Device attribute identifier types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceAttributeIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device attributes identifiers
	 */
	public const IDENTIFIER_HARDWARE_MANUFACTURER = 'hardware_manufacturer';

	public const IDENTIFIER_HARDWARE_MODEL = 'hardware_model';

	public const IDENTIFIER_HARDWARE_VERSION = 'hardware_version';

	public const IDENTIFIER_HARDWARE_MAC_ADDRESS = 'hardware_mac_address';

	public const IDENTIFIER_FIRMWARE_MANUFACTURER = 'firmware_manufacturer';

	public const IDENTIFIER_FIRMWARE_NAME = 'firmware_name';

	public const IDENTIFIER_FIRMWARE_VERSION = 'firmware_version';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
