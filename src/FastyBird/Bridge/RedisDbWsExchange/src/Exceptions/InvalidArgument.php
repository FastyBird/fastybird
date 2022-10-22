<?php declare(strict_types = 1);

/**
 * InvalidArgument.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbWsExchangeBridge!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Bridge\RedisDbWsExchange\Exceptions;

use InvalidArgumentException as PHPInvalidArgumentException;

class InvalidArgument extends PHPInvalidArgumentException implements Exception
{

}
