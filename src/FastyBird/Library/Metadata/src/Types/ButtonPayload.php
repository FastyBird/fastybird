<?php declare(strict_types = 1);

/**
 * ButtonPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
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
	public const PRESSED = 'btn_pressed';

	public const RELEASED = 'btn_released';

	public const CLICKED = 'btn_clicked';

	public const DOUBLE_CLICKED = 'btn_double_clicked';

	public const TRIPLE_CLICKED = 'btn_triple_clicked';

	public const LONG_CLICKED = 'btn_long_clicked';

	public const EXTRA_LONG_CLICKED = 'btn_extra_long_clicked';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
