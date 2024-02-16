<?php declare(strict_types = 1);

/**
 * Entities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           17.02.23
 */

namespace FastyBird\Connector\HomeKit\Subscribers;

use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Servers;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Symfony\Component\EventDispatcher;
use TypeError;
use ValueError;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Entities implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Servers\Http $httpServer,
		private readonly Servers\Mdns $mdnsServer,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\EntityCreated::class => 'entityCreated',
			DevicesEvents\EntityUpdated::class => 'entityUpdated',
			DevicesEvents\EntityDeleted::class => 'entityDeleted',
		];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function entityCreated(DevicesEvents\EntityCreated $event): void
	{
		if ($event->getEntity() instanceof DevicesEntities\Connectors\Properties\Variable) {
			if ($event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::SHARED_KEY) {
				$this->httpServer->setSharedKey($event->getEntity());
			}

			if (
				$event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::PAIRED
				|| $event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::CONFIG_VERSION
			) {
				$this->mdnsServer->refresh($event->getEntity());
			}
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function entityUpdated(DevicesEvents\EntityCreated $event): void
	{
		if ($event->getEntity() instanceof DevicesEntities\Connectors\Properties\Variable) {
			if ($event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::SHARED_KEY) {
				$this->httpServer->setSharedKey($event->getEntity());
			}

			if (
				$event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::PAIRED
				|| $event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::CONFIG_VERSION
			) {
				$this->mdnsServer->refresh($event->getEntity());
			}
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function entityDeleted(DevicesEvents\EntityCreated $event): void
	{
		if ($event->getEntity() instanceof DevicesEntities\Connectors\Properties\Variable) {
			if (
				$event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::PAIRED
				|| $event->getEntity()->getIdentifier() === Types\ConnectorPropertyIdentifier::CONFIG_VERSION
			) {
				$this->mdnsServer->refresh($event->getEntity());
			}
		}
	}

}
