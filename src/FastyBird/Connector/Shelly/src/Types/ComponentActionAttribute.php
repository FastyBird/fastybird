<?php declare(strict_types = 1);

/**
 * ComponentActionAttribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           22.12.23
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Generation 2 devices component action attribute
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ComponentActionAttribute extends Consistence\Enum\Enum
{

	public const ON = 'on';

	public const POSITION = 'pos';

	public const BRIGHTNESS = 'brightness';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
