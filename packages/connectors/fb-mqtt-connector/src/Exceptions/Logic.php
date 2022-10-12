<?php declare(strict_types = 1);

/**
 * Logic.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           15.10.21
 */

namespace FastyBird\FbMqttConnector\Exceptions;

use LogicException as PHPLogicException;

class Logic extends PHPLogicException implements Exception
{

}
