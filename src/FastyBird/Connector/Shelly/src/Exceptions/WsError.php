<?php declare(strict_types = 1);

/**
 * WsError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           25.08.23
 */

namespace FastyBird\Connector\Shelly\Exceptions;

use FastyBird\Connector\Tuya\Exceptions\Exception;
use RuntimeException;

class WsError extends RuntimeException implements Exception
{

}
