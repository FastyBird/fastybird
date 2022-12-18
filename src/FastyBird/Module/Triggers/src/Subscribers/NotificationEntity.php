<?php declare(strict_types = 1);

/**
 * ActionEntity.php
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
use function array_merge;

/**
 * Trigger notification entity listener
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NotificationEntity implements Common\EventSubscriber
{

	use Nette\SmartObject;

	/**
	 * Register events
	 *
	 * @return array<string>
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::onFlush,
		];
	}

	/**
	 * @throws Exceptions\UniqueNotificationEmailConstraint
	 * @throws Exceptions\UniqueNotificationNumberConstraint
	 */
	public function onFlush(ORM\Event\OnFlushEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach (array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates()) as $object) {
			if ($object instanceof Entities\Notifications\SmsNotification) {
				$trigger = $object->getTrigger();

				foreach ($trigger->getNotifications() as $notification) {
					if (
						!$notification->getId()->equals($object->getId())
						&& $notification instanceof Entities\Notifications\SmsNotification
						&& $notification->getPhone()->getInternationalNumber() === $object->getPhone()
							->getInternationalNumber()
					) {
						throw new Exceptions\UniqueNotificationNumberConstraint('Not same phone number in trigger');
					}
				}
			} elseif ($object instanceof Entities\Notifications\EmailNotification) {
				$trigger = $object->getTrigger();

				foreach ($trigger->getNotifications() as $notification) {
					if (
						!$notification->getId()->equals($object->getId())
						&& $notification instanceof Entities\Notifications\EmailNotification
						&& $notification->getEmail() === $object->getEmail()
					) {
						throw new Exceptions\UniqueNotificationEmailConstraint('Not same email address in trigger');
					}
				}
			}
		}
	}

}
