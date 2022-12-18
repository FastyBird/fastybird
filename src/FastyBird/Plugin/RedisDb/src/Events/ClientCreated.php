<?php declare(strict_types = 1);

/**
 * ClientCreated.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 * @since          0.2.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Plugin\RedisDb\Events;

use Clue\React\Redis;
use Symfony\Contracts\EventDispatcher;

/**
 * Client created event
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientCreated extends EventDispatcher\Event
{

	public function __construct(private readonly Redis\RedisClient $client)
	{
	}

	public function getClient(): Redis\RedisClient
	{
		return $this->client;
	}

}
