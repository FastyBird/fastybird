<?php declare(strict_types = 1);

/**
 * Terminate.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           02.11.22
 */

namespace FastyBird\Module\Triggers\Exceptions;

use Exception as PHPException;

class Terminate extends PHPException implements Exception
{

}
