<?php declare(strict_types = 1);

/**
 * OpenApiError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           20.05.23
 */

namespace FastyBird\Connector\Tuya\Exceptions;

use RuntimeException;

class OpenApiError extends RuntimeException implements Exception
{

}
