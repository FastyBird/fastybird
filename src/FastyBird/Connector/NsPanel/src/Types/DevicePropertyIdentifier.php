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
use FastyBird\Module\Devices\Types as DevicesTypes;
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

	public const STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	public const IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	public const DOMAIN = DevicesTypes\DevicePropertyIdentifier::DOMAIN->value;

	public const MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER->value;

	public const MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	public const MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	public const FIRMWARE_VERSION = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_VERSION->value;

	public const ACCESS_TOKEN = 'access_token';

	public const CATEGORY = 'category';

	public const GATEWAY_IDENTIFIER = 'gateway_identifier';

	public const STATE_READING_DELAY = DevicesTypes\DevicePropertyIdentifier::STATE_READING_DELAY->value;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
