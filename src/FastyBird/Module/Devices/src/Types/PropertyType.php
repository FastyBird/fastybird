<?php declare(strict_types = 1);

/**
 * PropertyType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           02.01.22
 */

namespace FastyBird\Module\Devices\Types;

/**
 * Property type
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum PropertyType: string
{

	case VARIABLE = 'variable';

	case DYNAMIC = 'dynamic';

	case MAPPED = 'mapped';

}
