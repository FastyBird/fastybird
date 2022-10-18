<?php declare(strict_types = 1);

/**
 * TriggerConditionOperator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Trigger condition operator type
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerConditionOperator extends Consistence\Enum\Enum
{

	/**
	 * Define states
	 */
	public const OPERATOR_VALUE_EQUAL = 'eq';

	public const OPERATOR_VALUE_ABOVE = 'above';

	public const OPERATOR_VALUE_BELOW = 'below';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
