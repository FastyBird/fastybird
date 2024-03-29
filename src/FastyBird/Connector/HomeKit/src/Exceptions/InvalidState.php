<?php declare(strict_types = 1);

/**
 * InvalidState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           18.09.22
 */

namespace FastyBird\Connector\HomeKit\Exceptions;

use RuntimeException;

class InvalidState extends RuntimeException implements Exception
{

}
