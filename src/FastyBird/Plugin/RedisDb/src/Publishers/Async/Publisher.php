<?php declare(strict_types = 1);

/**
 * Publisher.php
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

namespace FastyBird\Plugin\RedisDb\Publishers\Async;

use DateTimeInterface;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette;
use Psr\Log;
use React\Promise;
use Throwable;

/**
 * Redis DB exchange async publisher
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher implements ExchangePublisher\Async\Publisher
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Utilities\IdentifierGenerator $identifier,
		private readonly string $channel,
		private readonly Clients\Async\Client $client,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<true>
	 */
	public function publish(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataDocuments\Document|null $entity,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		try {
			$this->client->publish(
				$this->channel,
				Nette\Utils\Json::encode([
					'sender_id' => $this->identifier->getIdentifier(),
					'source' => $source->getValue(),
					'routing_key' => $routingKey->getValue(),
					'created' => $this->dateTimeFactory->getNow()->format(DateTimeInterface::ATOM),
					'data' => $entity?->toArray(),
				]),
			)
				->then(function () use ($source, $routingKey, $entity, $deferred): void {
					$this->logger->debug(
						'Received message was pushed into data exchange',
						[
							'source' => MetadataTypes\PluginSource::REDISDB,
							'type' => 'messages-async-publisher',
							'message' => [
								'routingKey' => $routingKey->getValue(),
								'source' => $source->getValue(),
								'data' => $entity?->toArray(),
							],
						],
					);

					$deferred->resolve(true);
				})
				->catch(function (Throwable $ex) use ($source, $routingKey, $entity, $deferred): void {
					$this->logger->error(
						'Received message could not be pushed into data exchange',
						[
							'source' => MetadataTypes\PluginSource::REDISDB,
							'type' => 'messages-async-publisher',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
							'message' => [
								'routingKey' => $routingKey->getValue(),
								'source' => $source->getValue(),
								'data' => $entity?->toArray(),
							],
						],
					);

					$deferred->reject(
						new Exceptions\InvalidState(
							'Message could not be published into exchange',
							$ex->getCode(),
							$ex,
						),
					);
				});
		} catch (Nette\Utils\JsonException $ex) {
			$this->logger->error(
				'Data could not be converted to message',
				[
					'source' => MetadataTypes\PluginSource::REDISDB,
					'type' => 'messages-async-publisher',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'message' => [
						'routingKey' => $routingKey->getValue(),
						'source' => $source->getValue(),
						'data' => $entity?->toArray(),
					],
				],
			);

			return Promise\reject(
				new Exceptions\InvalidArgument('Provided data could not be converted to message', $ex->getCode(), $ex),
			);
		}

		return $deferred->promise();
	}

}
