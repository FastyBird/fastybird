<?php declare(strict_types = 1);

/**
 * MotorControlPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Types;

use Consistence;
use function strval;

/**
 * Motor control capability supported payload types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MotorControlPayload extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const OPEN = 'open';

	public const CLOSE = 'close';

	public const STOP = 'stop';

	public const LOCK = 'lock';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
