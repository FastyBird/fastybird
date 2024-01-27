<?php declare(strict_types = 1);

/**
 * Message.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Handlers
 * @since          1.0.0
 *
 * @date           26.03.23
 */

namespace FastyBird\Plugin\RabbitMq\Handlers;

use Bunny;
use Evenement;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumer;
use FastyBird\Library\Exchange\Documents as ExchangeDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RabbitMq\Events;
use FastyBird\Plugin\RabbitMq\Exceptions;
use FastyBird\Plugin\RabbitMq\Utilities;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use Psr\Log;
use Throwable;
use function assert;
use function is_array;
use function strval;

/**
 * RabbitMQ message handler
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Handlers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Message extends Evenement\EventEmitter
{

	public const MESSAGE_ACK = 1;

	public const MESSAGE_NACK = 2;

	public const MESSAGE_REJECT = 3;

	public const MESSAGE_REJECT_AND_TERMINATE = 4;

	public function __construct(
		private readonly Utilities\IdentifierGenerator $identifier,
		private readonly ExchangeDocuments\DocumentFactory $documentFactory,
		private readonly ExchangeConsumer\Container $consumer,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	public function handle(Bunny\Message $message): int
	{
		$this->dispatcher?->dispatch(new Events\BeforeMessageHandled($message));

		try {
			$data = Nette\Utils\Json::decode($message->content, Nette\Utils\Json::FORCE_ARRAY);

			if (is_array($data) && $message->hasHeader('source')) {
				return $this->consume(
					strval($message->getHeader('source')),
					MetadataTypes\RoutingKey::get($message->routingKey),
					Nette\Utils\Json::encode($data),
					$message->hasHeader('sender_id') ? strval($message->getHeader('sender_id')) : null,
				);
			} else {
				// Log error action reason
				$this->logger->warning('Received message is not in valid format', [
					'source' => MetadataTypes\PluginSource::RABBITMQ,
					'type' => 'messages-handler',
				]);

				return self::MESSAGE_REJECT;
			}
		} catch (Nette\Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning('Received message is not valid json', [
				'source' => MetadataTypes\PluginSource::RABBITMQ,
				'type' => 'messages-handler',
				'exception' => ApplicationHelpers\Logger::buildException($ex),
			]);
		}

		$this->dispatcher?->dispatch(new Events\AfterMessageHandled($message));

		return self::MESSAGE_REJECT;
	}

	private function consume(
		string $source,
		MetadataTypes\RoutingKey $routingKey,
		string $data,
		string|null $senderId = null,
	): int
	{
		if ($senderId === $this->identifier->getIdentifier()) {
			return self::MESSAGE_NACK;
		}

		$source = $this->validateSource($source);

		if ($source === null) {
			return self::MESSAGE_REJECT;
		}

		try {
			$data = Utils\Json::decode($data, Utils\Json::FORCE_ARRAY);
			assert(is_array($data));
			$data = Utils\ArrayHash::from($data);

			$entity = $this->documentFactory->create($data, $routingKey);

		} catch (Throwable $ex) {
			$this->logger->error('Message could not be transformed into entity', [
				'source' => MetadataTypes\PluginSource::RABBITMQ,
				'type' => 'messages-handler',
				'exception' => ApplicationHelpers\Logger::buildException($ex),
				'data' => $data,
			]);

			return self::MESSAGE_REJECT;
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
				'source' => MetadataTypes\PluginSource::RABBITMQ,
				'type' => 'messages-handler',
				'exception' => ApplicationHelpers\Logger::buildException($ex),
			]);

			return self::MESSAGE_REJECT;
		}

		return self::MESSAGE_ACK;
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
