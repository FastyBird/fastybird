<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Controllers
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\WsExchangePlugin\Controllers;

use FastyBird\Exchange\Entities as ExchangeEntities;
use FastyBird\Exchange\Publisher as ExchangePublisher;
use FastyBird\Metadata;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Metadata\Loaders as MetadataLoaders;
use FastyBird\Metadata\Schemas as MetadataSchemas;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\WsExchangePlugin\Events;
use FastyBird\WsExchangePlugin\Exceptions;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette\Utils;
use Psr\EventDispatcher;
use Psr\Log;
use Throwable;
use function array_key_exists;

/**
 * Exchange sockets controller
 *
 * @package        FastyBird:MiniServer!
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
		private readonly ExchangePublisher\Container|null $publisher = null,
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
		$this->dispatcher?->dispatch(new Events\ClientSubscribed($client, $topic));
	}

	/**
	 * @phpstan-param array<mixed> $args
	 * @phpstan-param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 *
	 * @throws MetadataExceptions\FileNotFound
	 * @throws Utils\JsonException
	 */
	public function actionCall(
		array $args,
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
	): void
	{
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
				$schema = $this->schemaLoader->loadByRoutingKey(Metadata\Types\RoutingKey::get($args['routing_key']));
				$data = isset($args['data']) ? $this->parseData($args['data'], $schema) : null;

				$this->publisher?->publish(
					Metadata\Types\ModuleSource::get($args['source']),
					Metadata\Types\RoutingKey::get($args['routing_key']),
					$this->entityFactory->create(
						Utils\Json::encode($data),
						Metadata\Types\RoutingKey::get($args['routing_key']),
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
	 * @param array<mixed> $data
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
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgument('Provided data are not valid json format', 0, $ex);
		} catch (MetadataExceptions\InvalidData $ex) {
			$this->logger->debug('Received message is not valid', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'exchange-controller',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgument('Provided data are not in valid structure', 0, $ex);
		} catch (Throwable $ex) {
			$this->logger->error('Received message is not valid', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'exchange-controller',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgument('Provided data could not be validated', 0, $ex);
		}
	}

}
