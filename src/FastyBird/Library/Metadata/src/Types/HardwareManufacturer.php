<?php declare(strict_types = 1);

/**
 * HardwareManufacturer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           30.11.17
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Device hardware manufacturer type
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HardwareManufacturer extends Consistence\Enum\Enum
{

	/**
	 * Define data types
	 */
	public const MANUFACTURER_GENERIC = 'generic';

	public const MANUFACTURER_FASTYBIRD = 'fastybird';

	public const MANUFACTURER_ITEAD = 'itead';

	public const MANUFACTURER_AI_THINKER = 'ai_thinker';

	public const MANUFACTURER_SHELLY = 'shelly';

	public const MANUFACTURER_TUYA = 'tuya';

	public const MANUFACTURER_SONOFF = 'sonoff';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
