<?php declare(strict_types = 1);

/**
 * TriggerConditionOperator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Library\Metadata\Types;

/**
 * Trigger condition operator type
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum TriggerConditionOperator: string
{

	case EQUAL = 'eq';

	case ABOVE = 'above';

	case BELOW = 'below';

}
