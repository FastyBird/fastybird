<?php declare(strict_types = 1);

/**
 * RedisClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbTriggersModuleBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Bridge\RedisDbTriggersModule\Subscribers;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Events as TriggersEvents;
use FastyBird\Module\Triggers\Exceptions as TriggersExceptions;
use FastyBird\Plugin\RedisDb\Client as RedisDbClient;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Triggers module subscriber
 *
 * @package         FastyBird:RedisDbTriggersModuleBridge!
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

					throw new TriggersExceptions\Terminate(
						'Redis client could not be created',
						$ex->getCode(),
						$ex,
					);
				},
			);
	}

}
