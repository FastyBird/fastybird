<?php declare(strict_types = 1);

/**
 * TriggerType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.22.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Trigger type
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerType extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const TYPE_AUTOMATIC = 'automatic';

	public const TYPE_MANUAL = 'manual';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
