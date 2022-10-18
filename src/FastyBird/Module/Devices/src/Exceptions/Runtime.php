<?php declare(strict_types = 1);

/**
 * Runtime.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           17.03.20
 */

namespace FastyBird\Module\Devices\Exceptions;

use RuntimeException as PHPRuntimeException;

class Runtime extends PHPRuntimeException implements Exception
{

}
