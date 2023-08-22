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
	public const IP_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS;

	public const STATE = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE;

	public const MODEL = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL;

	public const MAC_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS;

	public const SERIAL_NUMBER = 'serial_number';

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

	public const READ_STATE_EXCLUDE_DPS = 'read_state_exclude_dps';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
