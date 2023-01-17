<?php declare(strict_types = 1);

/**
 * LocalApiTimeout.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           09.09.22
 */

namespace FastyBird\Connector\Tuya\Exceptions;

use RuntimeException;

class LocalApiTimeout extends RuntimeException implements Exception
{

}
