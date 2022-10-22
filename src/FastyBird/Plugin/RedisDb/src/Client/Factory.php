<?php declare(strict_types = 1);

/**
 * AsyncClient.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Client
 * @since          0.61.0
 *
 * @date           09.10.22
 */

namespace FastyBird\Plugin\RedisDb\Client;

use Clue\React\Redis;
use FastyBird\Plugin\RedisDb\Connections;
use FastyBird\Plugin\RedisDb\Events;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\Handlers;
use Psr\EventDispatcher;
use React\EventLoop;
use React\Promise;
use React\Socket;
use Throwable;

/**
 * Redis DB async client factory
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Client
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Factory
{

	public function __construct(
		private readonly string $channel,
		private readonly Connections\Connection $connection,
		private readonly Handlers\Message $messagesHandler,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function create(
		EventLoop\LoopInterface|null $eventLoop = null,
		Socket\ConnectorInterface|null $connector = null,
	): Promise\PromiseInterface
	{
		$factory = new Redis\Factory($eventLoop, $connector);

		$deferred = new Promise\Deferred();

		$factory->createClient($this->connection->getHost() . ':' . $this->connection->getPort())
			->then(
				function (Redis\Client $redis) use ($deferred): void {
					$this->dispatcher?->dispatch(new Events\ClientCreated($redis));

					$redis->subscribe($this->channel);

					$redis->on('message', function (string $channel, string $payload) use ($redis): void {
						try {
							$this->messagesHandler->handle($payload);
						} catch (Exceptions\Terminate) {
							$redis->close();
						}
					});

					$redis->on('close', function () use ($redis): void {
						$this->dispatcher?->dispatch(new Events\ConnectionClosed($redis));
					});

					$redis->on('error', function (Throwable $ex) use ($redis): void {
						$this->dispatcher?->dispatch(new Events\Error($ex, $redis));
					});

					$deferred->resolve($redis);
				},
				static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				},
			);

		return $deferred->promise();
	}

}
