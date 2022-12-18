<?php declare(strict_types = 1);

/**
 * Event.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Writers
 * @since          0.13.0
 *
 * @date           14.12.22
 */

namespace FastyBird\Connector\Tuya\Writers;

use FastyBird\Connector\Tuya\Clients;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use Nette;
use Symfony\Component\EventDispatcher;
use function assert;

/**
 * Event based properties writer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Event implements Writer, EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public const NAME = 'event';

	private Entities\TuyaConnector|null $connector = null;

	private Clients\Client|null $client = null;

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\StateEntityCreated::class => 'stateChanged',
			DevicesEvents\StateEntityUpdated::class => 'stateChanged',
		];
	}

	public function connect(
		Entities\TuyaConnector $connector,
		Clients\Client $client,
	): void
	{
		$this->connector = $connector;
		$this->client = $client;
	}

	public function disconnect(): void
	{
		// Nothing to do here
	}

	public function stateChanged(DevicesEvents\StateEntityCreated|DevicesEvents\StateEntityUpdated $event): void
	{
		$property = $event->getProperty();

		if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return;
		}

		if (!$property->getChannel()->getDevice()->getConnector()->getId()->equals($this->connector?->getId())) {
			return;
		}

		$device = $property->getChannel()->getDevice();
		$channel = $property->getChannel();

		assert($device instanceof Entities\TuyaDevice);

		$this->client?->writeChannelProperty($device, $channel, $property);
	}

}
