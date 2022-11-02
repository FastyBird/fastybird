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
 * Trigger condition entity listener
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConditionEntitySubscriber implements Common\EventSubscriber
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
			if ($object instanceof Entities\Conditions\IPropertyCondition) {
				$trigger = $object->getTrigger();

				foreach ($trigger->getConditions() as $condition) {
					if (!$condition->getId()->equals($object->getId())) {
						if (
							$condition instanceof Entities\Conditions\IDevicePropertyCondition
							&& $object instanceof Entities\Conditions\IDevicePropertyCondition
						) {
							if (
								$condition->getDevice()->equals($object->getDevice())
								&& $condition->getProperty()->equals($object->getProperty())
							) {
								throw new Exceptions\UniqueConditionConstraint('Not same property in trigger conditions');
							}
						} elseif (
							$condition instanceof Entities\Conditions\IChannelPropertyCondition
							&& $object instanceof Entities\Conditions\IChannelPropertyCondition
						) {
							if (
								$condition->getDevice()->equals($object->getDevice())
								&& $condition->getChannel()->equals($object->getChannel())
								&& $condition->getProperty()->equals($object->getProperty())
							) {
								throw new Exceptions\UniqueConditionConstraint('Not same property in trigger conditions');
							}
						}
					}
				}
			}
		}
	}

}
