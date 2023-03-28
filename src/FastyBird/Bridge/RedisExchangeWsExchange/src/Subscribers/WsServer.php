<?php declare(strict_types = 1);

/**
 * WsServer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisExchangeWsExchangeBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Bridge\RedisExchangeWsExchange\Subscribers;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients as RedisDbClient;
use FastyBird\Plugin\WsExchange\Consumers as WsExchangeConsumers;
use FastyBird\Plugin\WsExchange\Events as WsExchangeEvents;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\EventDispatcher;

/**
 * WS server events subscriber
 *
 * @package         FastyBird:WsExchange!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsServer implements EventDispatcher\EventSubscriberInterface
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly ExchangeConsumers\Container $consumer,
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
		$this->clientFactory->create($this->eventLoop);

		$this->consumer->enable(WsExchangeConsumers\Consumer::class);

		$this->logger->debug(
			'Redis client was successfully started with WS exchange server',
			[
				'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_WS_EXCHANGE,
				'type' => 'subscriber',
				'group' => 'subscriber',
			],
		);
	}

}
