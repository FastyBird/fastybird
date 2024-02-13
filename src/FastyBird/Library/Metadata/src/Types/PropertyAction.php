<?php declare(strict_types = 1);

/**
 * PropertyAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Library\Metadata\Types;

/**
 * Property action
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum PropertyAction: string
{

	case SET = 'set';

	case GET = 'get';

}
