<?php declare(strict_types = 1);

/**
 * TriggerActionType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.22.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Trigger action type
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerActionType extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const TYPE_DEVICE_PROPERTY = 'device_property';

	public const TYPE_CHANNEL_PROPERTY = 'channel_property';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
