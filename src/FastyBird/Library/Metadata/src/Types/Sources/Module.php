<?php declare(strict_types = 1);

/**
 * Module.php
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

namespace FastyBird\Library\Metadata\Types\Sources;

use FastyBird\Library\Metadata;

/**
 * Modules sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Module extends Source
{

	/**
	 * Define types
	 */
	public const NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	public const ACCOUNTS = Metadata\Constants::MODULE_ACCOUNTS_SOURCE;

	public const DEVICES = Metadata\Constants::MODULE_DEVICES_SOURCE;

	public const TRIGGERS = Metadata\Constants::MODULE_TRIGGERS_SOURCE;

	public const UI = Metadata\Constants::MODULE_UI_SOURCE;

}
