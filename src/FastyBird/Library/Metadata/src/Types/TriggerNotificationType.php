<?php declare(strict_types = 1);

/**
 * TriggerNotificationType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.22.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Trigger notification type
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerNotificationType extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const TYPE_EMAIL = 'email';

	public const TYPE_SMS = 'sms';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
