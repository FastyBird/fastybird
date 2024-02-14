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
use FastyBird\Module\Devices\Types as DevicesTypes;
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

	public const STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	public const MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER->value;

	public const MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	public const MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	public const STATE_PROCESSING_DELAY = DevicesTypes\DevicePropertyIdentifier::STATE_PROCESSING_DELAY->value;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
