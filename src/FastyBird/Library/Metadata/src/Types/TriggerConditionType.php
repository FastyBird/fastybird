<?php declare(strict_types = 1);

/**
 * TriggerConditionType.php
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

use Consistence;
use function strval;

/**
 * Trigger condition type
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerConditionType extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const TYPE_DUMMY = 'dummy';

	public const TYPE_DEVICE_PROPERTY = 'device_property';

	public const TYPE_CHANNEL_PROPERTY = 'channel_property';

	public const TYPE_TIME = 'time';

	public const TYPE_DATE = 'date';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
