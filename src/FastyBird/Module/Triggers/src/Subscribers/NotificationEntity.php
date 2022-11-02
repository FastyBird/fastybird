<?php declare(strict_types = 1);

/**
 * ActionEntitySubscriber.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Events
 * @since          0.1.0
 *
 * @date           05.04.20
 */

namespace FastyBird\Module\Triggers\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use Nette;

/**
 * Trigger notification entity listener
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NotificationEntitySubscriber implements Common\EventSubscriber
{

	use Nette\SmartObject;

	/**
	 * Register events
	 *
	 * @return string[]
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::onFlush,
		];
	}

	/**
	 * @param ORM\Event\OnFlushEventArgs $eventArgs
	 *
	 * @return void
	 */
	public function onFlush(ORM\Event\OnFlushEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach (array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates()) as $object) {
			if ($object instanceof Entities\Notifications\ISmsNotification) {
				$trigger = $object->getTrigger();

				foreach ($trigger->getNotifications() as $notification) {
					if (
						!$notification->getId()->equals($object->getId())
						&& $notification instanceof Entities\Notifications\ISmsNotification
						&& $notification->getPhone()->getInternationalNumber() === $object->getPhone()
							->getInternationalNumber()
					) {
						throw new Exceptions\UniqueNotificationNumberConstraint('Not same phone number in trigger');
					}
				}
			} elseif ($object instanceof Entities\Notifications\IEmailNotification) {
				$trigger = $object->getTrigger();

				foreach ($trigger->getNotifications() as $notification) {
					if (
						!$notification->getId()->equals($object->getId())
						&& $notification instanceof Entities\Notifications\IEmailNotification
						&& $notification->getEmail() === $object->getEmail()
					) {
						throw new Exceptions\UniqueNotificationEmailConstraint('Not same email address in trigger');
					}
				}
			}
		}
	}

}
