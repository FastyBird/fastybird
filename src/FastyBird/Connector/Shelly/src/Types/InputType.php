<?php declare(strict_types = 1);

/**
 * InputType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           27.12.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Input component input types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class InputType extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const TYPE_SWITCH = 'switch';

	public const TYPE_BUTTON = 'button';

	public const TYPE_ANALOG = 'analog';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
