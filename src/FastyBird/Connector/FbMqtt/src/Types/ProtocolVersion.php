<?php declare(strict_types = 1);

/**
 * ProtocolVersion.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Types;

use Consistence;
use function strval;

/**
 * Protocol versions
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ProtocolVersion extends Consistence\Enum\Enum
{

	/**
	 * Define states
	 */
	public const VERSION_1 = 'v1';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
