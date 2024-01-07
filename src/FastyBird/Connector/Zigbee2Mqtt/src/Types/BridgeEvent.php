<?php declare(strict_types = 1);

/**
 * BridgeEvent.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

use Consistence;
use function strval;

/**
 * Bridge events
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class BridgeEvent extends Consistence\Enum\Enum
{

	public const DEVICE_JOINED = 'device_joined';

	public const DEVICE_INTERVIEW = 'device_interview';

	public const DEVICE_LEAVE = 'device_leave';

	public const DEVICE_ANNOUNCE = 'device_announce';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
