<?php declare(strict_types = 1);

/**
 * EventLoopStarted.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           19.01.24
 */

namespace FastyBird\Library\Application\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Event loop was started event
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class EventLoopStarted extends EventDispatcher\Event
{

}
