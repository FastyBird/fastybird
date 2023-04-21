<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           17.4.23
 */

namespace FastyBird\Module\Devices\Consumers;

use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumer;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette\Utils;
use Psr\Log;
use Throwable;

/**
 * Exchange consumer
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Sockets implements ExchangeConsumer\Consumer
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly WebSockets\Router\LinkGenerator $linkGenerator,
		private readonly WebSocketsWAMP\Topics\IStorage $topicsStorage,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function consume(
		MetadataTypes\AutomatorSource|MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		if ($source->equalsValue(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES)) {
			return;
		}

		$result = $this->sendMessage(
			[
				'routing_key' => $routingKey->getValue(),
				'source' => $source->getValue(),
				'data' => $entity?->toArray(),
			],
		);

		if ($result) {
			$this->logger->debug('Successfully published message', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'exchange-consumer',
				'message' => [
					'routing_key' => $routingKey->getValue(),
					'source' => $source->getValue(),
					'data' => $entity?->toArray(),
				],
			]);

		} else {
			$this->logger->error('Message could not be published to exchange', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'exchange-consumer',
				'message' => [
					'routing_key' => $routingKey->getValue(),
					'source' => $source->getValue(),
					'data' => $entity?->toArray(),
				],
			]);
		}

		$this->logger->debug('Received message from exchange was pushed to WS clients', [
			'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
			'type' => 'exchange-consumer',
			'message' => [
				'source' => $source->getValue(),
				'routing_key' => $routingKey->getValue(),
				'entity' => $entity?->toArray(),
			],
		]);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function sendMessage(array $data): bool
	{
		try {
			$link = $this->linkGenerator->link('DevicesModule:Exchange:');

			if ($this->topicsStorage->hasTopic($link)) {
				$topic = $this->topicsStorage->getTopic($link);

				$this->logger->debug('Broadcasting message to topic', [
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'exchange-consumer',
					'link' => $link,
				]);

				$topic->broadcast(Utils\Json::encode($data));
			}

			return true;
		} catch (Utils\JsonException $ex) {
			$this->logger->error('Data could not be converted to message', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'exchange-consumer',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

		} catch (Throwable $ex) {
			$this->logger->error('Data could not be broadcasts to clients', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'exchange-consumer',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);
		}

		return false;
	}

}
