<?php declare(strict_types = 1);

/**
 * IdentityState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Account identity state type
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class IdentityState extends Consistence\Enum\Enum
{

	/**
	 * Define states
	 */
	public const STATE_ACTIVE = 'active';

	public const STATE_BLOCKED = 'blocked';

	public const STATE_DELETED = 'deleted';

	public const STATE_INVALID = 'invalid';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
