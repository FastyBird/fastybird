<?php declare(strict_types = 1);

/**
 * PropertyNotExists.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           22.03.20
 */

namespace FastyBird\Module\Accounts\Exceptions;

use Exception as PHPException;

class PropertyNotExists extends PHPException implements Exception
{

}
