<?php declare(strict_types = 1);

/**
 * ExposeType.php
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
 * Type data types
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ExposeType extends Consistence\Enum\Enum
{

	public const BINARY = 'binary';

	public const NUMERIC = 'numeric';

	public const ENUM = 'enum';

	public const TEXT = 'text';

	public const COMPOSITE = 'composite';

	public const LIST = 'list';

	public const LIGHT = 'light';

	public const SWITCH = 'switch';

	public const FAN = 'fan';

	public const COVER = 'cover';

	public const LOCK = 'lock';

	public const CLIMATE = 'climate';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
