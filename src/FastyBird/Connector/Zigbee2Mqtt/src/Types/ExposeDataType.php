<?php declare(strict_types = 1);

/**
 * ExposeDataType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           02.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

use Consistence;
use function strval;

/**
 * Expose data type types
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ExposeDataType extends Consistence\Enum\Enum
{

	public const SINGLE = 'single';

	public const COMPOSITE = 'composite';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
