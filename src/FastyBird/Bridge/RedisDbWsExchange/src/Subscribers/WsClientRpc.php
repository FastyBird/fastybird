<?php declare(strict_types = 1);

/**
 * WsClientRpc.php
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
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Loaders as MetadataLoaders;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Publishers as RedisDbPublishers;
use FastyBird\Plugin\WsExchange\Events as WsExchangeEvents;
use IPub\Phone\Exceptions as PhoneExceptions;
use Nette\Utils;
use Psr\Log;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * WS client RPC subscriber
 *
 * @package         FastyBird:RedisDbWsExchangeBridge!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsClientRpc implements EventDispatcher\EventSubscriberInterface
{

	protected Log\LoggerInterface $logger;

	public function __construct(
		private readonly MetadataLoaders\SchemaLoader $schemaLoader,
		private readonly MetadataSchemas\Validator $jsonValidator,
		private readonly MetadataEntities\RoutingFactory $entityFactory,
		private readonly RedisDbPublishers\Publisher $publisher,
		Log\LoggerInterface|null $logger,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			WsExchangeEvents\ClientRpc::class => 'clientRpc',
		];
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

				$this->publisher->publish(
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
