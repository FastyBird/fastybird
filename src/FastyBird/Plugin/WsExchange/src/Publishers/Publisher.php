<?php declare(strict_types = 1);

/**
 * Sender.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Publishers
 * @since          0.2.0
 *
 * @date           08.10.21
 */

namespace FastyBird\Plugin\WsExchange\Publishers;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette;
use Psr\Log;
use Throwable;

/**
 * Websockets exchange publisher
 *
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	/**
	 * @phpstan-param WebSocketsWAMP\Topics\IStorage<WebSocketsWAMP\Entities\Topics\Topic> $topicsStorage
	 */
	public function __construct(
		private readonly WebSockets\Router\LinkGenerator $linkGenerator,
		private readonly WebSocketsWAMP\Topics\IStorage $topicsStorage,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function publish(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		$result = $this->sendMessage(
			'Exchange:',
			[
				'routing_key' => $routingKey->getValue(),
				'origin' => $source->getValue(),
				'data' => $entity?->toArray(),
			],
		);

		if ($result) {
			$this->logger->debug('Successfully published message', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'message-publisher',
				'group' => 'publisher',
				'message' => [
					'routing_key' => $routingKey->getValue(),
					'origin' => $source->getValue(),
					'data' => $entity?->toArray(),
				],
			]);

		} else {
			$this->logger->error('Message could not be published to exchange', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'message-publisher',
				'group' => 'publisher',
				'message' => [
					'routing_key' => $routingKey->getValue(),
					'origin' => $source->getValue(),
					'data' => $entity?->toArray(),
				],
			]);
		}
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function sendMessage(string $destination, array $data): bool
	{
		try {
			$link = $this->linkGenerator->link($destination);

			if ($this->topicsStorage->hasTopic($link)) {
				$topic = $this->topicsStorage->getTopic($link);

				$this->logger->debug('Broadcasting message to topic', [
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
					'type' => 'message-publisher',
					'group' => 'publisher',
					'link' => $link,
				]);

				$topic->broadcast(Nette\Utils\Json::encode($data));

				return true;
			}
		} catch (Nette\Utils\JsonException $ex) {
			$this->logger->error('Data could not be converted to message', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'message-publisher',
				'group' => 'publisher',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

		} catch (Throwable $ex) {
			$this->logger->error('Data could not be broadcasts to clients', [
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
				'type' => 'message-publisher',
				'group' => 'publisher',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);
		}

		return false;
	}

}
