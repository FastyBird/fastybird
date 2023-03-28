<?php declare(strict_types = 1);

/**
 * RedisClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisExchangeTriggersModuleBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Bridge\RedisExchangeTriggersModule\Subscribers;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Events as TriggersEvents;
use FastyBird\Plugin\RedisDb\Clients as RedisDbClient;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\EventDispatcher;

/**
 * Triggers module subscriber
 *
 * @package         FastyBird:RedisExchangeTriggersModuleBridge!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisClient implements EventDispatcher\EventSubscriberInterface
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
			TriggersEvents\AutomatorStartup::class => 'automatorStartup',
		];
	}

	public function automatorStartup(): void
	{
		$this->clientFactory->create($this->eventLoop);

		$this->logger->debug(
			'Redis client was successfully started with devices service',
			[
				'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_TRIGGERS_MODULE,
				'type' => 'subscriber',
				'group' => 'subscriber',
			],
		);
	}

}
