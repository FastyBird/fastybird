<?php declare(strict_types = 1);

/**
 * EventLoopStopping.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           20.01.24
 */

namespace FastyBird\Library\Application\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Event loop is going to be stopped event
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class EventLoopStopping extends EventDispatcher\Event
{

}
