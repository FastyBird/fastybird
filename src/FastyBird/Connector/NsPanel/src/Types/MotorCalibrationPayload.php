<?php declare(strict_types = 1);

/**
 * MotorCalibrationPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Types;

use Consistence;
use function strval;

/**
 * Motor calibration capability supported payload types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MotorCalibrationPayload extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const CALIBRATION = 'calibration';

	public const NORMAL = 'normal';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
