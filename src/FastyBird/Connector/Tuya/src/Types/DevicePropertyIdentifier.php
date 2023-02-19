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
use FastyBird\Library\Metadata\Types as MetadataTypes;
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

	/**
	 * Define device states
	 */
	public const IDENTIFIER_IP_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS;

	public const IDENTIFIER_STATE = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE;

	public const IDENTIFIER_HARDWARE_MODEL = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public const IDENTIFIER_HARDWARE_MAC_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

	public const IDENTIFIER_SERIAL_NUMBER = 'serial_number';

	public const IDENTIFIER_PROTOCOL_VERSION = 'protocol_version';

	public const IDENTIFIER_LOCAL_KEY = 'local_key';

	public const IDENTIFIER_NODE_ID = 'node_id';

	public const IDENTIFIER_GATEWAY_ID = 'gateway_id';

	public const IDENTIFIER_CATEGORY = 'category';

	public const IDENTIFIER_ICON = 'icon';

	public const IDENTIFIER_LATITUDE = 'lat';

	public const IDENTIFIER_LONGITUDE = 'lon';

	public const IDENTIFIER_PRODUCT_ID = 'product_id';

	public const IDENTIFIER_PRODUCT_NAME = 'product_name';

	public const IDENTIFIER_ENCRYPTED = 'encrypted';

	public const IDENTIFIER_STATUS_READING_DELAY = 'status_reading_delay';

	public const IDENTIFIER_READ_STATE_EXCLUDE_DPS = 'read_state_exclude_dps';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
