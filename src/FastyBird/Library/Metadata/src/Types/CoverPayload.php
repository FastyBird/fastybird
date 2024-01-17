<?php declare(strict_types = 1);

/**
 * CoverPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           27.12.22
 */

namespace FastyBird\Library\Metadata\Types;

use Consistence;
use function strval;

/**
 * Cover/Roller supported payload types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class CoverPayload extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const PAYLOAD_OPEN = 'cover_open';

	public const PAYLOAD_OPENING = 'cover_opening';

	public const PAYLOAD_OPENED = 'cover_opened';

	public const PAYLOAD_CLOSE = 'cover_close';

	public const PAYLOAD_CLOSING = 'cover_closing';

	public const PAYLOAD_CLOSED = 'cover_closed';

	public const PAYLOAD_STOP = 'cover_stop';

	public const PAYLOAD_STOPPED = 'cover_stopped';

	public const PAYLOAD_CALIBRATE = 'cover_calibrate';

	public const PAYLOAD_CALIBRATING = 'cover_calibrating';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
