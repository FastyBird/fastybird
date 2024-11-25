<?php declare(strict_types = 1);

/**
 * InvalidArgument.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           19.12.20
 */

namespace FastyBird\Core\Application\Exceptions;

use LogicException;

class Mapping extends LogicException implements Exception
{

}
