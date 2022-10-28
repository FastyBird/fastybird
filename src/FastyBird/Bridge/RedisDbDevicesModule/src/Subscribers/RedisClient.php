<?php declare(strict_types = 1);

/**
 * RedisClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\Subscribers;

use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Module\Devices\Consumers as DevicesConsumers;
use FastyBird\Plugin\RedisDb\Events as RedisDbEvents;
use Symfony\Component\EventDispatcher;

/**
 * Redis DB client subscriber
 *
 * @package         FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisClient implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(
		private readonly ExchangeConsumers\Container $consumer,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			RedisDbEvents\Startup::class => 'startup',
		];
	}

	public function startup(): void
	{
		$this->consumer->enable(DevicesConsumers\States::class);
	}

}
