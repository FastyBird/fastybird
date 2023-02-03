<?php declare(strict_types = 1);

/**
 * RtuFunction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           03.02.23
 */

namespace FastyBird\Connector\Modbus\Types;

use Consistence;
use function strval;

/**
 * RTU function types
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RtuFunction extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const FUNCTION_CODE_READ_COIL = 0x01;

	public const FUNCTION_CODE_READ_DISCRETE = 0x02;

	public const FUNCTION_CODE_READ_HOLDING = 0x03;

	public const FUNCTION_CODE_READ_INPUT = 0x04;

	public const FUNCTION_CODE_WRITE_SINGLE_COIL = 0x05;

	public const FUNCTION_CODE_WRITE_SINGLE_HOLDING = 0x06;

	public const FUNCTION_CODE_WRITE_MULTIPLE_COILS = 0x1F;

	public const FUNCTION_CODE_WRITE_MULTIPLE_HOLDINGS = 0x10;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
