<?php declare(strict_types = 1);

/**
 * AccountState.php
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

/**
 * Account state type
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum AccountState: string
{

	case ACTIVE = 'active';

	case BLOCKED = 'blocked';

	case DELETED = 'deleted';

	case NOT_ACTIVATED = 'not_activated';

	case APPROVAL_WAITING = 'approval_waiting';

	/**
	 * @return array<self>
	 */
	public static function getAllowed(): array
	{
		return [
			self::ACTIVE,
			self::BLOCKED,
			self::DELETED,
		];
	}

}
