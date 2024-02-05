<?php declare(strict_types = 1);

/**
 * Switcher.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Library\Metadata\Types\Payloads;

/**
 * Switch supported payload types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Switcher extends Payload
{

	/**
	 * Define types
	 */
	public const ON = 'switch_on';

	public const OFF = 'switch_off';

	public const TOGGLE = 'switch_toggle';

}
