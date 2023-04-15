<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           15.01.22
 */

namespace FastyBird\Plugin\WsExchange\Controllers;

use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Loaders as MetadataLoaders;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\WsExchange\Events;
use FastyBird\Plugin\WsExchange\Exceptions;
use IPub\Phone\Exceptions as PhoneExceptions;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette\Utils;
use Psr\EventDispatcher;
use Psr\Log;
use Throwable;
use function array_key_exists;
use function is_array;

/**
 * Exchange sockets controller
 *
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Exchange extends WebSockets\Application\Controller\Controller
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly MetadataLoaders\SchemaLoader $schemaLoader,
		private readonly MetadataSchemas\Validator $jsonValidator,
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly ExchangePublisher\Publisher $exchangePublisher,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		Log\LoggerInterface|null $logger = null,
	)
	{
		parent::__construct();

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @phpstan-param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 */
	public function actionSubscribe(
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Client subscribed to topic',
			[
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'exchange-controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
			],
		);

		$this->dispatcher?->dispatch(new Events\ClientSubscribed($client, $topic));
	}

	/**
	 * @phpstan-param array<string, mixed> $args
	 * @phpstan-param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
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
	public function actionCall(
		array $args,
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Received RPC call from client',
			[
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'exchange-controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
				'data' => $args,
			],
		);

		if (!array_key_exists('routing_key', $args) || !array_key_exists('source', $args)) {
			throw new Exceptions\InvalidArgument('Provided message has invalid format');
		}

		$this->dispatcher?->dispatch(new Events\ClientRpc($client, $topic, $args));

		switch ($args['routing_key']) {
			case Metadata\Constants::MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_TRIGGER_CONTROL_ACTION_ROUTING_KEY:
				$schema = $this->schemaLoader->loadByRoutingKey(
					MetadataTypes\RoutingKey::get($args['routing_key']),
				);

				/** @var array<string, mixed>|null $data */
				$data = isset($args['data']) && is_array($args['data']) ? $args['data'] : null;
				$data = $data !== null ? $this->parseData($data, $schema) : null;

				$this->exchangePublisher->publish(
					MetadataTypes\ModuleSource::get($args['source']),
					MetadataTypes\RoutingKey::get($args['routing_key']),
					$this->entityFactory->create(
						Utils\Json::encode($data),
						MetadataTypes\RoutingKey::get($args['routing_key']),
					),
				);

				break;
			default:
				throw new Exceptions\InvalidArgument('Provided message has unsupported routing key');
		}

		$this->payload->data = [
			'response' => 'accepted',
		];
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function parseData(array $data, string $schema): Utils\ArrayHash
	{
		try {
			return $this->jsonValidator->validate(Utils\Json::encode($data), $schema);
		} catch (Utils\JsonException $ex) {
			$this->logger->error('Received message could not be validated', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'exchange-controller',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

			throw new Exceptions\InvalidArgument('Provided data are not valid json format', 0, $ex);
		} catch (MetadataExceptions\InvalidData $ex) {
			$this->logger->debug('Received message is not valid', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'exchange-controller',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

			throw new Exceptions\InvalidArgument('Provided data are not in valid structure', 0, $ex);
		} catch (Throwable $ex) {
			$this->logger->error('Received message is not valid', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'exchange-controller',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

			throw new Exceptions\InvalidArgument('Provided data could not be validated', 0, $ex);
		}
	}

}
