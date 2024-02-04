<?php declare(strict_types = 1);

/**
 * InvalidState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           11.01.24
 */

namespace FastyBird\Addon\VirtualThermostat\Exceptions;

use RuntimeException;

class InvalidState extends RuntimeException implements Exception
{

}
