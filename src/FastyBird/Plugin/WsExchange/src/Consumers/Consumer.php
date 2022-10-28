<?php declare(strict_types = 1);

/**
 * Consumer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Consumers
 * @since          0.2.0
 *
 * @date           27.10.22
 */

namespace FastyBird\Plugin\WsExchange\Consumers;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumer;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\WsExchange\Publishers as WsExchangePublishers;
use Psr\Log;

/**
 * Exchange consumer
 *
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Consumer implements ExchangeConsumer\Consumer
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly WsExchangePublishers\Publisher $publisher,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function consume(
		MetadataTypes\TriggerSource|MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		$this->publisher->publish($source, $routingKey, $entity);

		$this->logger->warning('Received message from exchange was pushed to WS clients', [
			'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_WS_EXCHANGE,
			'type' => 'consumer',
			'message' => [
				'source' => $source->getValue(),
				'routing_key' => $routingKey->getValue(),
				'entity' => $entity?->toArray(),
			],
		]);
	}

}
