<?php declare(strict_types = 1);

/**
 * ModuleSource.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           26.04.21
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use FastyBird\Library\Metadata;
use function strval;

/**
 * Modules sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ModuleSource extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const SOURCE_NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	public const SOURCE_MODULE_ACCOUNTS = Metadata\Constants::MODULE_ACCOUNTS_SOURCE;

	public const SOURCE_MODULE_DEVICES = Metadata\Constants::MODULE_DEVICES_SOURCE;

	public const SOURCE_MODULE_TRIGGERS = Metadata\Constants::MODULE_TRIGGERS_SOURCE;

	public const SOURCE_MODULE_UI = Metadata\Constants::MODULE_UI_SOURCE;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
