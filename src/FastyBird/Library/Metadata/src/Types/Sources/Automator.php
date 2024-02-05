<?php declare(strict_types = 1);

/**
 * Automator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           19.01.22
 */

namespace FastyBird\Library\Metadata\Types\Sources;

use FastyBird\Library\Metadata;

/**
 * Triggers automators sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Automator extends Source
{

	/**
	 * Define types
	 */
	public const NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	public const DEVICE_MODULE = Metadata\Constants::AUTOMATOR_DEVICE_MODULE;

	public const DATE_TIME = Metadata\Constants::AUTOMATOR_DATE_TIME;

}
