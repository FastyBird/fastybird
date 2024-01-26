<?php declare(strict_types = 1);

/**
 * Factory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Exchange
 * @since          1.0.0
 *
 * @date           09.10.22
 */

namespace FastyBird\Plugin\RedisDb\Exchange;

use Clue\React\Redis;
use FastyBird\Library\Exchange\Events as ExchangeEvents;
use FastyBird\Library\Exchange\Exchange as ExchangeExchange;
use FastyBird\Plugin\RedisDb\Connections;
use FastyBird\Plugin\RedisDb\Events;
use Psr\EventDispatcher;
use React\EventLoop;
use Throwable;

/**
 * Redis DB exchange factory
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Exchange
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Factory implements ExchangeExchange\Factory
{

	public function __construct(
		private readonly string $channel,
		private readonly Connections\Configuration $connection,
		private readonly Handler $messagesHandler,
		private readonly EventLoop\LoopInterface|null $eventLoop = null,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function create(): void
	{
		$redis = new Redis\RedisClient(
			$this->connection->getHost() . ':' . $this->connection->getPort(),
			null,
			$this->eventLoop,
		);

		$redis->on('close', function (): void {
			$this->dispatcher?->dispatch(new Events\ConnectionClosed());
		});

		$redis->on('error', function (Throwable $ex): void {
			$this->dispatcher?->dispatch(new ExchangeEvents\ExchangeError($ex));
		});

		$redis->on('message', function (string $channel, string $payload): void {
			if ($channel === $this->channel) {
				$this->messagesHandler->handle($payload);
			}
		});

		$redis->subscribe($this->channel);
	}

}