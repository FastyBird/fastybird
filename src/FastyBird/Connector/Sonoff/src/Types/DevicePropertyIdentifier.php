<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           14.05.23
 */

namespace FastyBird\Connector\Sonoff\Types;

use Consistence;
use FastyBird\Module\Devices\Types as DevicesTypes;
use function strval;

/**
 * Device property identifier types
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	public const ADDRESS = DevicesTypes\DevicePropertyIdentifier::ADDRESS->value;

	public const STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	public const HARDWARE_MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	public const HARDWARE_MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	public const FIRMWARE_VERSION = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_VERSION->value;

	public const RSSI = DevicesTypes\DevicePropertyIdentifier::RSSI->value;

	public const SSID = DevicesTypes\DevicePropertyIdentifier::SSID->value;

	public const STATUS_LED = DevicesTypes\DevicePropertyIdentifier::STATUS_LED->value;

	public const API_KEY = 'api_key';

	public const DEVICE_KEY = 'device_key';

	public const BRAND_NAME = 'brand_name';

	public const BRAND_LOGO = 'brand_logo';

	public const PRODUCT_MODEL = 'product_model';

	public const PORT = 'port';

	public const UIID = 'uiid';

	public const STATE_READING_DELAY = 'state_reading_delay';

	public const HEARTBEAT_DELAY = 'heartbeat_delay';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
