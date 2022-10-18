<?php declare(strict_types = 1);

/**
 * ButtonPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          0.7.6
 *
 * @date           17.11.21
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Button supported payload types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ButtonPayload extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const PAYLOAD_PRESSED = 'btn_pressed';

	public const PAYLOAD_RELEASED = 'btn_released';

	public const PAYLOAD_CLICKED = 'btn_clicked';

	public const PAYLOAD_DOUBLE_CLICKED = 'btn_double_clicked';

	public const PAYLOAD_TRIPLE_CLICKED = 'btn_triple_clicked';

	public const PAYLOAD_LONG_CLICKED = 'btn_long-clicked';

	public const PAYLOAD_EXTRA_LONG_CLICKED = 'btn_extra_long_clicked';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
