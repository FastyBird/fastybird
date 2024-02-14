<?php declare(strict_types = 1);

/**
 * DeviceCategory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           09.04.23
 */

namespace FastyBird\Module\Devices\Types;

/**
 * Device category
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum DeviceCategory: string
{

	case GENERIC = 'generic';

}
