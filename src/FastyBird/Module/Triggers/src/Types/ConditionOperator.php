<?php declare(strict_types = 1);

/**
 * ConditionOperator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Types;

/**
 * Trigger condition operator type
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ConditionOperator: string
{

	case EQUAL = 'eq';

	case ABOVE = 'above';

	case BELOW = 'below';

}
