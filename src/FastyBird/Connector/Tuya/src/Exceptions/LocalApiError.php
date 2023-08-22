<?php declare(strict_types = 1);

/**
 * LocalApiError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           14.08.23
 */

namespace FastyBird\Connector\Tuya\Exceptions;

use RuntimeException;

class LocalApiError extends RuntimeException implements Exception
{

}
