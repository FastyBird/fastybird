<?php declare(strict_types = 1);

/**
 * Runtime.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Tools!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           20.01.24
 */

namespace FastyBird\Core\Tools\Exceptions;

use RuntimeException as PHPRuntimeException;

class Runtime extends PHPRuntimeException implements Exception
{

}
