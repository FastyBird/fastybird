<?php declare(strict_types = 1);

/**
 * SwitchPayload.php
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

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Switch supported payload types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SwitchPayload extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const PAYLOAD_ON = 'switch_on';

	public const PAYLOAD_OFF = 'switch_off';

	public const PAYLOAD_TOGGLE = 'switch_toggle';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
