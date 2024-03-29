<?php declare(strict_types = 1);

/**
 * NotReachable.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           02.08.22
 */

namespace FastyBird\Connector\Modbus\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class NotReachable extends PHPInvalidArgumentException implements Exception
{

}
