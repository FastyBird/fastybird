<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Types;

use Consistence;
use FastyBird\Module\Devices\Types as DevicesTypes;
use function strval;

/**
 * Device property identifier types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	public const IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	public const STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	public const MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	public const MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	public const SERIAL_NUMBER = DevicesTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value;

	public const PROTOCOL_VERSION = 'protocol_version';

	public const LOCAL_KEY = 'local_key';

	public const NODE_ID = 'node_id';

	public const GATEWAY_ID = 'gateway_id';

	public const CATEGORY = 'category';

	public const ICON = 'icon';

	public const LATITUDE = 'lat';

	public const LONGITUDE = 'lon';

	public const PRODUCT_ID = 'product_id';

	public const PRODUCT_NAME = 'product_name';

	public const ENCRYPTED = 'encrypted';

	public const STATE_READING_DELAY = 'state_reading_delay';

	public const HEARTBEAT_DELAY = 'heartbeat_delay';

	public const READ_STATE_EXCLUDE_DPS = 'read_state_exclude_dps';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
