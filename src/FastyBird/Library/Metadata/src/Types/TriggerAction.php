<?php declare(strict_types = 1);

/**
 * TriggerAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Trigger action
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerAction extends Consistence\Enum\Enum
{

	/**
	 * Define actions
	 */
	public const ACTION_SET = 'set';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
