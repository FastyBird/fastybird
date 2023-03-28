<?php declare(strict_types = 1);

/**
 * RedisClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisExchangeDevicesModuleBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Bridge\RedisExchangeDevicesModule\Subscribers;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Consumers as DevicesConsumers;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Plugin\RedisDb\Clients as RedisDbClient;
use FastyBird\Plugin\RedisDb\Events as RedisDbEvents;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\EventDispatcher;

/**
 * Devices module subscriber
 *
 * @package         FastyBird:RedisExchangeDevicesModuleBridge!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisClient implements EventDispatcher\EventSubscriberInterface
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly RedisDbClient\Factory $clientFactory,
		private readonly ExchangeConsumers\Container $consumer,
		private readonly EventLoop\LoopInterface|null $eventLoop = null,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\ConnectorStartup::class => 'connectorStartup',
			RedisDbEvents\Startup::class => 'exchangeStartup',
		];
	}

	public function connectorStartup(): void
	{
		$this->clientFactory->create($this->eventLoop);

		$this->logger->debug(
			'Redis client was successfully started with devices service',
			[
				'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_DEVICES_MODULE,
				'type' => 'subscriber',
				'group' => 'subscriber',
			],
		);
	}

	public function exchangeStartup(): void
	{
		$this->consumer->enable(DevicesConsumers\State::class);
	}

}
