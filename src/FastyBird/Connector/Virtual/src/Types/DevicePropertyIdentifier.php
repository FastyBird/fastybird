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

	public const STATE = MetadataTypes\DevicePropertyIdentifier::STATE;

	public const MANUFACTURER = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER;

	public const MAC_ADDRESS = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS;

	public const MODEL = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MODEL;

	public const STATE_PROCESSING_DELAY = MetadataTypes\DevicePropertyIdentifier::STATE_PROCESSING_DELAY;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
