<?php declare(strict_types = 1);

/**
 * Message.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Handlers
 * @since          1.0.0
 *
 * @date           09.10.21
 */

namespace FastyBird\Plugin\RedisDb\Handlers;

use Evenement;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumer;
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Events;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\Utilities;
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
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Handlers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Message extends Evenement\EventEmitter
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Utilities\IdentifierGenerator $identifier,
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly ExchangeConsumer\Container $consumer,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
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
					'type' => 'messages-handler',
				]);
			}
		} catch (Nette\Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning('Received message is not valid json', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'messages-handler',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
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
		if ($senderId === $this->identifier->getIdentifier()) {
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
				'type' => 'messages-handler',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

			return;
		}

		try {
			$this->dispatcher?->dispatch(new Events\MessageReceived(
				$source,
				$routingKey,
				$entity,
			));

			$this->consumer->consume($source, $routingKey, $entity);

			$this->emit('message', [$source, $routingKey, $entity]);

		} catch (Exceptions\UnprocessableMessage $ex) {
			// Log error consume reason
			$this->logger->error('Message could not be handled', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'messages-handler',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

			return;
		}
	}

	private function validateSource(
		string $source,
	): MetadataTypes\ModuleSource|MetadataTypes\ConnectorSource|MetadataTypes\PluginSource|MetadataTypes\AutomatorSource|null
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

		if (MetadataTypes\AutomatorSource::isValidValue($source)) {
			return MetadataTypes\AutomatorSource::get($source);
		}

		return null;
	}

}
