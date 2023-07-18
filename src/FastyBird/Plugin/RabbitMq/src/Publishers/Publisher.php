<?php declare(strict_types = 1);

/**
 * Publisher.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Publisher
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RabbitMq\Publishers;

use Bunny;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RabbitMq\Channels;
use FastyBird\Plugin\RabbitMq\Utilities;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\Promise;
use Throwable;
use function is_bool;
use function strval;
use const DATE_ATOM;

/**
 * RabbitMQ exchange publisher
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Publisher
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher implements ExchangePublisher\Publisher
{

	use Nette\SmartObject;

	private Bunny\Channel|null $asyncChannel = null;

	public function __construct(
		private readonly string $exchangeName,
		private readonly Channels\Channel $channel,
		private readonly Utilities\IdentifierGenerator $identifier,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @internal
	 */
	public function setChannel(Bunny\Channel $channel): void
	{
		$this->asyncChannel = $channel;
	}

	public function publish(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		try {
			// Compose message
			$body = Utils\Json::encode($entity?->toArray());

		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Data could not be converted to message',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_RABBITMQ,
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

		$result = $this->getChannel()->publish(
			$body,
			[
				'sender_id' => $this->identifier->getIdentifier(),
				'source' => $source->getValue(),
				'created' => $this->dateTimeFactory->getNow()->format(DATE_ATOM),
			],
			$this->exchangeName,
			strval($routingKey->getValue()),
		);

		if (is_bool($result)) {
			if ($result) {
				$this->logger->debug(
					'Received message was pushed into data exchange',
					[
						'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_RABBITMQ,
						'type' => 'messages-publisher',
						'message' => [
							'routingKey' => $routingKey->getValue(),
							'source' => $source->getValue(),
							'data' => $entity?->toArray(),
							'body' => $body,
						],
					],
				);
			} else {
				$this->logger->error(
					'Received message could not be pushed into data exchange',
					[
						'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_RABBITMQ,
						'type' => 'messages-publisher',
						'message' => [
							'routingKey' => $routingKey->getValue(),
							'source' => $source->getValue(),
							'data' => $entity?->toArray(),
							'body' => $body,
						],
					],
				);
			}
		} elseif ($result instanceof Promise\PromiseInterface) {
			$result
				->then(
					function () use ($routingKey, $source, $entity, $body): void {
						$this->logger->debug(
							'Received message was pushed into data exchange',
							[
								'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_RABBITMQ,
								'type' => 'messages-publisher',
								'message' => [
									'routingKey' => $routingKey->getValue(),
									'source' => $source->getValue(),
									'data' => $entity?->toArray(),
									'body' => $body,
								],
							],
						);
					},
					function (Throwable $ex) use ($routingKey, $source, $entity, $body): void {
						$this->logger->error(
							'Received message could not be pushed into data exchange',
							[
								'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_RABBITMQ,
								'type' => 'messages-publisher',
								'message' => [
									'routingKey' => $routingKey->getValue(),
									'source' => $source->getValue(),
									'data' => $entity?->toArray(),
									'body' => $body,
								],
								'exception' => BootstrapHelpers\Logger::buildException($ex),
							],
						);
					},
				);
		}
	}

	private function getChannel(): Channels\Channel|Bunny\Channel
	{
		if ($this->asyncChannel !== null) {
			return $this->asyncChannel;
		}

		return $this->channel;
	}

}
