<?php declare(strict_types = 1);

/**
 * Publishers.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Publishers
 * @since          1.0.0
 *
 * @date           17.09.21
 */

namespace FastyBird\Plugin\RedisDb\Publishers;

use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette;
use Psr\Log;
use const DATE_ATOM;

/**
 * Redis DB exchange publisher
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher implements ExchangePublisher\Publisher
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Utilities\IdentifierGenerator $identifier,
		private readonly string $channel,
		private readonly Clients\Client $client,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
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
		try {
			$result = $this->client->publish(
				$this->channel,
				Nette\Utils\Json::encode([
					'sender_id' => $this->identifier->getIdentifier(),
					'source' => $source->getValue(),
					'routing_key' => $routingKey->getValue(),
					'created' => $this->dateTimeFactory->getNow()->format(DATE_ATOM),
					'data' => $entity?->toArray(),
				]),
			);

		} catch (Nette\Utils\JsonException $ex) {
			$this->logger->error(
				'Data could not be converted to message',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
					'type' => 'messages-publisher',
					'message' => [
						'routingKey' => $routingKey->getValue(),
						'source' => $source->getValue(),
						'data' => $entity?->toArray(),
					],
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			return;
		}

		if ($result === true) {
			$this->logger->debug(
				'Received message was pushed into data exchange',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
					'type' => 'messages-publisher',
					'message' => [
						'routingKey' => $routingKey->getValue(),
						'source' => $source->getValue(),
						'data' => $entity?->toArray(),
					],
				],
			);
		} else {
			$this->logger->error(
				'Received message could not be pushed into data exchange',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
					'type' => 'messages-publisher',
					'message' => [
						'routingKey' => $routingKey->getValue(),
						'source' => $source->getValue(),
						'data' => $entity?->toArray(),
					],
				],
			);
		}
	}

}
