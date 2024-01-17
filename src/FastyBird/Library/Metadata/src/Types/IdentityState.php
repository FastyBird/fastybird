<?php declare(strict_types = 1);

/**
 * IdentityState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Account identity state type
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class IdentityState extends Consistence\Enum\Enum
{

	/**
	 * Define states
	 */
	public const ACTIVE = 'active';

	public const BLOCKED = 'blocked';

	public const DELETED = 'deleted';

	public const INVALID = 'invalid';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
