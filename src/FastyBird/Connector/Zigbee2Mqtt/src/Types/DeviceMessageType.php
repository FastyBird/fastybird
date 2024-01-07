<?php declare(strict_types = 1);

/**
 * BridgeMessageType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

use Consistence;
use function strval;

/**
 * Device message types
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceMessageType extends Consistence\Enum\Enum
{

	public const GET = 'get';

	public const AVAILABILITY = 'availability';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
