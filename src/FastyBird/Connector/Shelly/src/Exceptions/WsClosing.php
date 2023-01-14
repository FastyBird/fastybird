<?php declare(strict_types = 1);

/**
 * WsClosing.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Exceptions
 * @since          0.13.0
 *
 * @date           09.01.23
 */

namespace FastyBird\Connector\Shelly\Exceptions;

use RuntimeException;

class WsClosing extends RuntimeException implements Exception
{

}
