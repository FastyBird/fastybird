<?php declare(strict_types = 1);

/**
 * InitializeSubscriber.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           21.12.20
 */

namespace FastyBird\Plugin\RabbitMq\Subscribers;

use FastyBird\ApplicationEvents\Events as ApplicationEventsEvents;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * Rabbit MQ initialise subscriber
 *
 * @package         FastyBird:RabbitMqPlugin!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class InitializeSubscriber implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(private RabbitMqPlugin\Exchange $exchange)
	{
	}

	/**
	 * @return array<string>
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationEventsEvents\StartupEvent::class => 'initialize',
		];
	}

	/**
	 * @throws Throwable
	 */
	public function initialize(): void
	{
		$this->exchange->initializeAsync();
	}

}
