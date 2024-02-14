<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           18.06.23
 */

namespace FastyBird\Connector\Viera\Types;

use Consistence;
use FastyBird\Module\Devices\Types as DevicesTypes;
use function strval;

/**
 * Device property identifier types
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	public const IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	public const STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	public const MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	public const MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER->value;

	public const MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	public const SERIAL_NUMBER = DevicesTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value;

	public const PORT = 'port';

	public const ENCRYPTED = 'encrypted';

	public const APP_ID = 'app_id';

	public const ENCRYPTION_KEY = 'encryption_key';

	public const STATE_READING_DELAY = 'state_reading_delay';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
