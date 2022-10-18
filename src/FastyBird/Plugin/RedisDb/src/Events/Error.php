<?php declare(strict_types = 1);

/**
 * Error.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 * @since          0.2.0
 *
 * @date           09.10.21
 */

namespace FastyBird\Plugin\RedisDb\Events;

use Clue\React\Redis;
use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Connection error event
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Error extends EventDispatcher\Event
{

	public function __construct(private readonly Throwable $ex, private readonly Redis\Client $client)
	{
	}

	public function getException(): Throwable
	{
		return $this->ex;
	}

	public function getClient(): Redis\Client
	{
		return $this->client;
	}

}
