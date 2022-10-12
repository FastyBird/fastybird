<?php declare(strict_types = 1);

/**
 * ClientVersion.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          0.37.0
 *
 * @date           19.07.22
 */

namespace FastyBird\ShellyConnector\Types;

use Consistence;
use function strval;

/**
 * Connector client versions types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientVersion extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const TYPE_GEN_1 = 'gen1';

	public const TYPE_GEN_2 = 'gen2';

	public const TYPE_CLOUD = 'cloud';

	public const TYPE_INTEGRATOR = 'integrator';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
