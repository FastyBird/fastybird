<?php declare(strict_types = 1);

/**
 * Startup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Events
 * @since          0.3.0
 *
 * @date           05.10.21
 */

namespace FastyBird\WebServerPlugin\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * When web server started
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Startup extends EventDispatcher\Event
{

}
