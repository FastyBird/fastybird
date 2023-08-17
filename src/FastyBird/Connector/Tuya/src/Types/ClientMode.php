<?php declare(strict_types = 1);

/**
 * ClientMode.php
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
 * Connector client modes types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientMode extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const LOCAL = 'local';

	public const CLOUD = 'cloud';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
