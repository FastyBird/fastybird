<?php declare(strict_types = 1);

/**
 * InvalidState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           10.03.20
 */

namespace FastyBird\Plugin\WebServer\Exceptions;

use RuntimeException;

class InvalidState extends RuntimeException implements Exception
{

}
