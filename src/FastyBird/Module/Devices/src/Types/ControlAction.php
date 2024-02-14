<?php declare(strict_types = 1);

/**
 * ControlAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Module\Devices\Types;

/**
 * Control action
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ControlAction: string
{

	case SET = 'set';

}
