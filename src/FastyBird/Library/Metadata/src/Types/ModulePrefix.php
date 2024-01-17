<?php declare(strict_types = 1);

/**
 * ModulePrefix.php
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
 * Modules prefixes types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ModulePrefix extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const ACCOUNTS = Metadata\Constants::MODULE_ACCOUNTS_PREFIX;

	public const DEVICES = Metadata\Constants::MODULE_DEVICES_PREFIX;

	public const TRIGGERS = Metadata\Constants::MODULE_TRIGGERS_PREFIX;

	public const UI = Metadata\Constants::MODULE_UI_PREFIX;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
