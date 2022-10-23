<?php declare(strict_types = 1);

/**
 * Connector.php
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

use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\DataStorage as DevicesDataStorage;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Plugin\RedisDb\Client as RedisDbClient;
use FastyBird\Plugin\RedisDb\Handlers as RedisDbHandlers;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\EventDispatcher;
use Throwable;
use function strval;

/**
 * Devices connector subscriber
 *
 * @package         FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Connector implements EventDispatcher\EventSubscriberInterface
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly RedisDbClient\Factory $clientFactory,
		private readonly RedisDbHandlers\Message $messageHandler,
		private readonly DevicesDataStorage\Reader $reader,
		private readonly EventLoop\LoopInterface|null $eventLoop = null,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\ConnectorStartup::class => 'startup',
		];
	}

	public function startup(): void
	{
		$this->clientFactory->create($this->eventLoop)
			->then(
				function (): void {
					$this->messageHandler->on(
						'message',
						function (
							MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\TriggerSource $source,
							MetadataTypes\RoutingKey $routingKey,
							MetadataEntities\Entity|null $entity,
						): void {
							if (
								Utils\Strings::startsWith(
									strval($routingKey->getValue()),
									Metadata\Constants::MESSAGE_BUS_ENTITY_PREFIX_KEY,
								)
								&& (
									Utils\Strings::contains(
										strval($routingKey->getValue()),
										Metadata\Constants::MESSAGE_BUS_ENTITY_CREATED_KEY,
									)
									|| Utils\Strings::contains(
										strval($routingKey->getValue()),
										Metadata\Constants::MESSAGE_BUS_ENTITY_UPDATED_KEY,
									)
									|| Utils\Strings::contains(
										strval($routingKey->getValue()),
										Metadata\Constants::MESSAGE_BUS_ENTITY_DELETED_KEY,
									)
								)
							) {
								$this->reader->read();
							}
						},
					);

					$this->logger->debug(
						'Redis client was successfully started with devices connector',
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

}
