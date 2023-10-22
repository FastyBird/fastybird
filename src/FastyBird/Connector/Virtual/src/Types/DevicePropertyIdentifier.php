<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Types;

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device property identifier types
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	public const STATE = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE;

	public const MANUFACTURER = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER;

	public const MODEL = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public const STATE_READING_DELAY = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE_READING_DELAY;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
