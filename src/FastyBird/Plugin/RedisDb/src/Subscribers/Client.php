<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\RedisDb\Subscribers;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Events;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\Publishers;
use FastyBird\Plugin\RedisDb\States;
use Psr\Log;
use Symfony\Component\EventDispatcher;

/**
 * React async client subscriber
 *
 * @package         FastyBird:RedisDbPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Client implements EventDispatcher\EventSubscriberInterface
{

	private Log\LoggerInterface $logger;

	/**
	 * @phpstan-param Models\StatesRepositoryFactory<States\State> $statesRepositoryFactory
	 * @phpstan-param Models\StatesManagerFactory<States\State> $statesManagerFactory
	 */
	public function __construct(
		private readonly Publishers\Publisher $publisher,
		private readonly Models\StatesRepositoryFactory $statesRepositoryFactory,
		private readonly Models\StatesManagerFactory $statesManagerFactory,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\ClientCreated::class => 'clientCreated',
		];
	}

	public function clientCreated(Events\ClientCreated $event): void
	{
		$this->publisher->setAsyncClient($event->getClient());

		$this->statesRepositoryFactory->setAsyncClient($event->getClient());
		$this->statesManagerFactory->setAsyncClient($event->getClient());

		$this->logger->debug(
			'Redis async client was assigned to publisher service and models services',
			[
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'subscriber',
				'group' => 'subscriber',
			],
		);
	}

}
