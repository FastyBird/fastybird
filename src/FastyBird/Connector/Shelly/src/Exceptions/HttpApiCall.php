<?php declare(strict_types = 1);

/**
 * HttpApiCall.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\Exceptions;

use RuntimeException;

class HttpApiCall extends RuntimeException implements Exception
{

}
