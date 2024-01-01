<?php declare(strict_types = 1);

/**
 * DeviceType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

use Consistence;
use function strval;

/**
 * Device types
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceType extends Consistence\Enum\Enum
{

	public const END_DEVICE = 'EndDevice';

	public const COORDINATOR = 'Coordinator';

	public const ROUTER = 'Router';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
