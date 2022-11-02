<?php declare(strict_types = 1);

/**
 * NotImplementedException.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Exceptions
 * @since          0.21.0
 *
 * @date           09.02.22
 */

namespace FastyBird\Module\Triggers\Exceptions;

use RuntimeException;

class NotImplementedException extends RuntimeException implements IException
{

}
