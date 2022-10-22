<?php declare(strict_types = 1);

/**
 * RedisMessageReceived.php
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
use FastyBird\Plugin\RedisDb\Events as RedisDbEvents;
use FastyBird\Plugin\WsExchange\Publishers as WsExchangePublishers;
use Psr\Log;
use Symfony\Component\EventDispatcher;

/**
 * Redis DB message received subscriber
 *
 * @package         FastyBird:RedisDbWsExchangeBridge!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisMessageReceived implements EventDispatcher\EventSubscriberInterface
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly WsExchangePublishers\Publisher $publisher,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			RedisDbEvents\MessageReceived::class => 'messageReceived',
		];
	}

	public function messageReceived(RedisDbEvents\MessageReceived $event): void
	{
		$this->publisher->publish($event->getSource(), $event->getRoutingKey(), $event->getEntity());

		$this->logger->warning('Received message from exchange was pushed to WS clients', [
			'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_WS_EXCHANGE,
			'type' => 'subscriber',
			'message' => [
				'source' => $event->getSource()->getValue(),
				'routing_key' => $event->getRoutingKey()->getValue(),
				'entity' => $event->getEntity()?->toArray(),
			],
		]);
	}

}
