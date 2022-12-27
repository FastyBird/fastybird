<?php declare(strict_types = 1);

/**
 * MessageSource.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Types;

use Consistence;
use function strval;

/**
 * Message client source types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MessageSource extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const SOURCE_GEN_1_COAP = 'gen_1_coap';

	public const SOURCE_GEN_1_HTTP = 'gen_1_http';

	public const SOURCE_GEN_2_HTTP = 'gen_2_http';

	public const SOURCE_LOCAL_DISCOVERY = 'local_discovery';

	public const SOURCE_CLOUD_DISCOVERY = 'cloud_discovery';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
