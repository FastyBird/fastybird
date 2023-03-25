<?php declare(strict_types = 1);

/**
 * UnprocessableMessageException.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           19.12.20
 */

namespace FastyBird\Plugin\RabbitMq\Exceptions;

class UnprocessableMessageException extends InvalidStateException implements IException
{

}
