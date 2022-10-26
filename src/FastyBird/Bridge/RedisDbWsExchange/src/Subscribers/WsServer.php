<?php declare(strict_types = 1);

/**
 * WsServer.php
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

use FastyBird\Bridge\RedisDbWsExchange\Exceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Loaders as MetadataLoaders;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Client as RedisDbClient;
use FastyBird\Plugin\RedisDb\Handlers as RedisDbHandlers;
use FastyBird\Plugin\WsExchange\Events as WsExchangeEvents;
use FastyBird\Plugin\WsExchange\Publishers as WsExchangePublishers;
use IPub\Phone\Exceptions as PhoneExceptions;
use IPub\WebSockets;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\EventDispatcher;
use Throwable;

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
		private readonly MetadataLoaders\SchemaLoader $schemaLoader,
		private readonly MetadataSchemas\Validator $jsonValidator,
		private readonly MetadataEntities\RoutingFactory $entityFactory,
		private readonly RedisDbClient\Factory $clientFactory,
		private readonly RedisDbHandlers\Message $messageHandler,
		private readonly ExchangePublisher\Container $exchangePublisher,
		private readonly WsExchangePublishers\Publisher $wsPublisher,
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
			WsExchangeEvents\ClientRpc::class => 'clientRpc',
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
							$this->exchangePublisher->publish($source, $routingKey, $entity);

							$this->logger->warning('Received message from exchange was pushed to WS clients', [
								'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_WS_EXCHANGE,
								'type' => 'subscriber',
								'message' => [
									'source' => $source->getValue(),
									'routing_key' => $routingKey->getValue(),
									'entity' => $entity?->toArray(),
								],
							]);
						},
					);

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

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	public function clientRpc(WsExchangeEvents\ClientRpc $event): void
	{
		switch ($event->getArgs()['routing_key']) {
			case Metadata\Constants::MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_TRIGGER_CONTROL_ACTION_ROUTING_KEY:
				$schema = $this->schemaLoader->loadByRoutingKey(
					MetadataTypes\RoutingKey::get($event->getArgs()['routing_key']),
				);
				$data = isset($event->getArgs()['data']) ? $this->parseData($event->getArgs()['data'], $schema) : null;

				$this->wsPublisher->publish(
					MetadataTypes\ModuleSource::get($event->getArgs()['source']),
					MetadataTypes\RoutingKey::get($event->getArgs()['routing_key']),
					$this->entityFactory->create(
						Utils\Json::encode($data),
						MetadataTypes\RoutingKey::get($event->getArgs()['routing_key']),
					),
				);

				break;
			default:
				$this->logger->error(
					'Provided message has unsupported routing key',
					[
						'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_WS_EXCHANGE,
						'type' => 'subscriber',
					],
				);
		}
	}

	/**
	 * @param Array<string, mixed> $data
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function parseData(array $data, string $schema): Utils\ArrayHash
	{
		try {
			return $this->jsonValidator->validate(Utils\Json::encode($data), $schema);
		} catch (Utils\JsonException $ex) {
			$this->logger->error('Received message could not be validated', [
				'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_WS_EXCHANGE,
				'type' => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgument('Provided data are not valid json format', 0, $ex);
		} catch (MetadataExceptions\InvalidData $ex) {
			$this->logger->debug('Received message is not valid', [
				'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_WS_EXCHANGE,
				'type' => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgument('Provided data are not in valid structure', 0, $ex);
		} catch (Throwable $ex) {
			$this->logger->error('Received message is not valid', [
				'source' => MetadataTypes\BridgeSource::SOURCE_BRIDGE_REDISDB_WS_EXCHANGE,
				'type' => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgument('Provided data could not be validated', 0, $ex);
		}
	}

}
