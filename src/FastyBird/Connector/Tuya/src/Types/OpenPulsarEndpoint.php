<?php declare(strict_types = 1);

/**
 * OpenPulsarEndpoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Types;

use Consistence;
use function strval;

/**
 * OpenPulsar endpoint types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class OpenPulsarEndpoint extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const CHINA = 'wss://mqe.tuyacn.com:8285/';

	public const AMERICA = 'wss://mqe.tuyaus.com:8285/';

	public const EUROPE = 'wss://mqe.tuyaeu.com:8285/';

	public const INDIA = 'wss://mqe.tuyain.com:8285/';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
