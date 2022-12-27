<?php declare(strict_types = 1);

/**
 * DeviceGeneration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Device generations
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DeviceGeneration extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const GENERATION_1 = 'gen1';

	public const GENERATION_2 = 'gen2';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
