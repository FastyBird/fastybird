<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           13.02.23
 */

namespace FastyBird\Connector\HomeKit\Types;

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device property identifier types
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device properties identifiers
	 */
	public const CATEGORY = 'category';

	public const TYPE = 'type';

	public const AID = 'aid';

	public const MANUFACTURER = MetadataTypes\DevicePropertyIdentifier::FIRMWARE_MANUFACTURER;

	public const VERSION = MetadataTypes\DevicePropertyIdentifier::FIRMWARE_VERSION;

	public const SERIAL_NUMBER = MetadataTypes\DevicePropertyIdentifier::SERIAL_NUMBER;

	public const MODEL = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MODEL;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
