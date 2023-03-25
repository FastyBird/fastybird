<?php declare(strict_types = 1);

/**
 * TerminateException.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RabbitMq\Exceptions;

use Exception;

class TerminateException extends Exception implements IException
{

}
