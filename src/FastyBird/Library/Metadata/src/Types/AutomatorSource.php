<?php declare(strict_types = 1);

/**
 * AutomatorSource.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          0.30.0
 *
 * @date           19.01.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use FastyBird\Library\Metadata;
use function strval;

/**
 * Triggers automators sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AutomatorSource extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const SOURCE_NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	public const SOURCE_AUTOMATOR_DEVICE_MODULE = Metadata\Constants::AUTOMATOR_DEVICE_MODULE;

	public const SOURCE_AUTOMATOR_DATE_TIME = Metadata\Constants::AUTOMATOR_DATE_TIME;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
