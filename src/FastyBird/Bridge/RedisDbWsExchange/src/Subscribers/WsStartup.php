<?php declare(strict_types = 1);

/**
 * WsStartup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbWsExchangeBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Bridge\RedisDbWsExchange\Subscribers;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Client as RedisDbClient;
use FastyBird\Plugin\WsExchange\Events as WsExchangeEvents;
use IPub\WebSockets;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * WS start-up event subscriber
 *
 * @package         FastyBird:WsExchange!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsStartup implements EventDispatcher\EventSubscriberInterface
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly RedisDbClient\Factory $clientFactory,
		private readonly EventLoop\LoopInterface|null $eventLoop = null,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			WsExchangeEvents\Startup::class => 'startup',
		];
	}

	public function startup(): void
	{
		$this->clientFactory->create($this->eventLoop)
			->then(
				function (): void {
					$this->logger->debug(
						'Redis client was successfully started with WS exchange server',
						[
							'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_WS_EXCHANGE,
							'type' => 'subscriber',
						],
					);
				},
				function (Throwable $ex): void {
					$this->logger->error(
						'Redis client could not be created',
						[
							'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_WS_EXCHANGE,
							'type' => 'subscriber',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
						],
					);

					throw new WebSockets\Exceptions\TerminateException(
						'Redis client could not be created',
						$ex->getCode(),
						$ex,
					);
				},
			);
	}

}
