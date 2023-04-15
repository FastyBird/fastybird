<?php declare(strict_types = 1);

/**
 * WsStartup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           05.10.21
 */

namespace FastyBird\Plugin\WsExchange\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * When WS server started
 *
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Startup extends EventDispatcher\Event
{

}
