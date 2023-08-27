<?php declare(strict_types = 1);

/**
 * CoapError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           26.08.23
 */

namespace FastyBird\Connector\Shelly\Exceptions;

use RuntimeException;

class CoapError extends RuntimeException implements Exception
{

}
