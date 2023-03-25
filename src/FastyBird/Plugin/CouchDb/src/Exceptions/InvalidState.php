<?php declare(strict_types = 1);

/**
 * InvalidState.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           10.03.20
 */

namespace FastyBird\Plugin\CouchDb\Exceptions;

use RuntimeException;

class InvalidState extends RuntimeException implements Exception
{

}
