<?php declare(strict_types = 1);

/**
 * WritableSensorType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Writable sensor types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WritableSensorType extends Consistence\Enum\Enum
{

	/**
	 * Define device states
	 */
	public const TYPE_MODE = 'mode';

	public const TYPE_OUTPUT = 'output';

	public const TYPE_ROLLER = 'roller';

	public const TYPE_RED = 'red';

	public const TYPE_GREEN = 'green';

	public const TYPE_BLUE = 'blue';

	public const TYPE_WHITE = 'white';

	public const TYPE_GAIN = 'gain';

	public const TYPE_COLOR_TEMP = 'colorTemp';

	public const TYPE_BRIGHTNESS = 'brightness';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
