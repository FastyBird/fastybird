<?php declare(strict_types = 1);

/**
 * Terminate.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServer!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           05.12.20
 */

namespace FastyBird\Plugin\WebServer\Exceptions;

use Exception;
use Throwable;

class Terminate extends Exception implements Throwable
{

}
