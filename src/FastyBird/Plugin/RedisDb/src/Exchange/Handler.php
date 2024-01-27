<?php declare(strict_types = 1);

/**
 * Handler.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Exchange
 * @since          1.0.0
 *
 * @date           09.10.21
 */

namespace FastyBird\Plugin\RedisDb\Exchange;

use Evenement;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumer;
use FastyBird\Library\Exchange\Documents as ExchangeDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette;
use Nette\Utils;
use Psr\Log;
use Throwable;
use function array_key_exists;
use function assert;
use function is_array;
use function strval;

/**
 * Redis client message handler
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Exchange
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Handler extends Evenement\EventEmitter
{

	public function __construct(
		private readonly Utilities\IdentifierGenerator $identifier,
		private readonly ExchangeDocuments\DocumentFactory $documentFactory,
		private readonly ExchangeConsumer\Container $consumer,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	public function handle(string $payload): void
	{
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
				$this->logger->warning(
					'Received message is not in valid format',
					[
						'source' => MetadataTypes\PluginSource::REDISDB,
						'type' => 'messages-handler',
					],
				);
			}
		} catch (Nette\Utils\JsonException $ex) {
			// Log error action reason
			$this->logger->warning(
				'Received message is not valid json',
				[
					'source' => MetadataTypes\PluginSource::REDISDB,
					'type' => 'messages-handler',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);
		}
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
			$data = Utils\Json::decode($data, Utils\Json::FORCE_ARRAY);
			assert(is_array($data));
			$data = Utils\ArrayHash::from($data);

			$entity = $this->documentFactory->create($data, $routingKey);

		} catch (Throwable $ex) {
			$this->logger->error(
				'Message could not be transformed into entity',
				[
					'source' => MetadataTypes\PluginSource::REDISDB,
					'type' => 'messages-handler',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'data' => $data,
				],
			);

			return;
		}

		$this->consumer->consume($source, $routingKey, $entity);

		$this->emit('message', [$source, $routingKey, $entity]);
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
