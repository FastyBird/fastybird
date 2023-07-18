<?php declare(strict_types = 1);

/**
 * PowerPayload.php
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
 * Toggle capability supported payload types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PowerPayload extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const ON = 'on';

	public const OFF = 'off';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
