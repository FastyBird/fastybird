<?php declare(strict_types = 1);

/**
 * Terminate.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           17.09.21
 */

namespace FastyBird\Plugin\RedisDb\Exceptions;

use Exception;
use Throwable;

class Terminate extends Exception implements Throwable
{

}
