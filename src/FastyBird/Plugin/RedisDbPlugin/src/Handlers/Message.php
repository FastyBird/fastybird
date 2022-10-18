<?php declare(strict_types = 1);

/**
 * Message.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Handlers
 * @since          0.2.0
 *
 * @date           09.10.21
 */

namespace FastyBird\RedisDbPlugin\Handlers;

use FastyBird\Library\Exchange\Consumer as ExchangeConsumer;
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\RedisDbPlugin\Events;
use FastyBird\RedisDbPlugin\Exceptions;
use FastyBird\RedisDbPlugin\Utils;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use Psr\Log;
use Throwable;
use function array_key_exists;
use function is_array;
use function strval;

/**
 * Redis client message handler
 *
 * @package         FastyBird:RedisDbPlugin!
 * @subpackage      Handlers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Message
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Utils\IdentifierGenerator $identifier,
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly ExchangeConsumer\Consumer|null $consumer = null,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function handle(string $payload): void
	{
		$this->dispatcher?->dispatch(new Events\BeforeMessageHandled($payload));

		try {
			$data = Nette\Utils\Json::decode($payload, Nette\Utils\Json::FORCE_ARRAY);

			if (
				is_array($data)
				&& array_key_exists('source', $data)
				&& array_key_exists('routing_key', $data)
				&& array_key_exists('data', $data)
			) {
				$this->consume(
					strval($data['source']),
					MetadataTypes\RoutingKey::get($data['routing_key']),
					Nette\Utils\Json::encode($data['data']),
					array_key_exists('sender_id', $data) ? $data['sender_id'] : null,
				);

			} else {
				// Log error action reason
				$this->logger->warning('Received message is not in valid format', [
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
					'type' => 'subscriber',
				]);
			}
		} catch (Nette\Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning('Received message is not valid json', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);
		}

		$this->dispatcher?->dispatch(new Events\AfterMessageHandled($payload));
	}

	private function consume(
		string $source,
		MetadataTypes\RoutingKey $routingKey,
		string $data,
		string|null $senderId = null,
	): void
	{
		if ($this->consumer === null) {
			return;
		}

		if ($senderId === $this->identifier->getIdentifier()) {
			$this->logger->debug('Received message published by itself', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'subscriber',
			]);

			return;
		}

		$source = $this->validateSource($source);

		if ($source === null) {
			return;
		}

		try {
			$entity = $this->entityFactory->create($data, $routingKey);

		} catch (Throwable $ex) {
			$this->logger->error('Message could not be transformed into entity', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			return;
		}

		try {
			$this->consumer->consume($source, $routingKey, $entity);

		} catch (Exceptions\UnprocessableMessage $ex) {
			// Log error consume reason
			$this->logger->error('Message could not be handled', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'subscriber',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			return;
		}
	}

	private function validateSource(
		string $source,
	): MetadataTypes\ModuleSource|MetadataTypes\ConnectorSource|MetadataTypes\PluginSource|MetadataTypes\TriggerSource|null
	{
		if (MetadataTypes\ModuleSource::isValidValue($source)) {
			return MetadataTypes\ModuleSource::get($source);
		}

		if (MetadataTypes\PluginSource::isValidValue($source)) {
			return MetadataTypes\PluginSource::get($source);
		}

		if (MetadataTypes\ConnectorSource::isValidValue($source)) {
			return MetadataTypes\ConnectorSource::get($source);
		}

		if (MetadataTypes\TriggerSource::isValidValue($source)) {
			return MetadataTypes\TriggerSource::get($source);
		}

		return null;
	}

}
