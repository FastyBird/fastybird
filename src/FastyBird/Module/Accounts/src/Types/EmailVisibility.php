<?php declare(strict_types = 1);

/**
 * EmailVisibility.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Types;

use Consistence;
use function strval;

/**
 * Doctrine2 DB type for email visibility type column
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class EmailVisibility extends Consistence\Enum\Enum
{

	/**
	 * Define states
	 */
	public const PUBLIC = 'public';

	public const PRIVATE = 'private';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
