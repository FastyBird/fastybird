<?php declare(strict_types = 1);

/**
 * KeyStateType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Types;

/**
 * API access key state
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum KeyState: string
{

	case ACTIVE = 'active';

	case SUSPENDED = 'suspended';

	case DELETED = 'deleted';

}
