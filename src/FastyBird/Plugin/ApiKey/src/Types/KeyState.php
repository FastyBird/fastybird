<?php declare(strict_types = 1);

/**
 * KeyStateType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Types
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Types;

use Consistence;
use function strval;

/**
 * API access key state
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class KeyState extends Consistence\Enum\Enum
{

	/**
	 * Define states
	 */
	public const STATE_ACTIVE = 'active';

	public const STATE_SUSPENDED = 'suspended';

	public const STATE_DELETED = 'deleted';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
