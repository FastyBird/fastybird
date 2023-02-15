<?php declare(strict_types = 1);

/**
 * WsCallTimeout.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           09.01.23
 */

namespace FastyBird\Connector\Shelly\Exceptions;

use RuntimeException;

class WsCallTimeout extends RuntimeException implements Exception
{

}
