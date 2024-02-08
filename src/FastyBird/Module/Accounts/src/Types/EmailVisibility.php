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

/**
 * Email visibility types
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum EmailVisibility: string
{

	case PUBLIC = 'public';

	case PRIVATE = 'private';

}
