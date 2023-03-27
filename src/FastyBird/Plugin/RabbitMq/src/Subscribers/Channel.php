<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\RabbitMq\Subscribers;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RabbitMq\Events;
use FastyBird\Plugin\RabbitMq\Publishers;
use Psr\Log;
use Symfony\Component\EventDispatcher;

/**
 * RabbitMQ async client channel subscriber
 *
 * @package         FastyBird:RabbitMqPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Channel implements EventDispatcher\EventSubscriberInterface
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Publishers\Publisher $publisher,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\ChannelCreated::class => 'channelCreated',
		];
	}

	public function channelCreated(Events\ChannelCreated $event): void
	{
		$this->publisher->setChannel($event->getChannel());

		$this->logger->debug(
			'Rabbit MQ channel from async client was assigned to publisher service',
			[
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_RABBITMQ,
				'type' => 'subscriber',
				'group' => 'subscriber',
			],
		);
	}

}
