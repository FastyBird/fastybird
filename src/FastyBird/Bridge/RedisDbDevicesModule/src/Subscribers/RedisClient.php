<?php declare(strict_types = 1);

/**
 * RedisClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\Subscribers;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Consumers as DevicesConsumers;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Plugin\RedisDb\Client as RedisDbClient;
use FastyBird\Plugin\RedisDb\Events as RedisDbEvents;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Devices module subscriber
 *
 * @package         FastyBird:RedisDbDevicesModuleBridge!
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
		$this->clientFactory->create($this->eventLoop)
			->then(
				function (): void {
					$this->logger->debug(
						'Redis client was successfully started with devices service',
						[
							'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_DEVICES_STATES,
							'type' => 'subscriber',
						],
					);
				},
				function (Throwable $ex): void {
					$this->logger->error(
						'Redis client could not be created',
						[
							'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_DEVICES_STATES,
							'type' => 'subscriber',
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
						],
					);

					throw new DevicesExceptions\Terminate(
						'Redis client could not be created',
						$ex->getCode(),
						$ex,
					);
				},
			);
	}

	public function exchangeStartup(): void
	{
		$this->consumer->enable(DevicesConsumers\States::class);
	}

}
