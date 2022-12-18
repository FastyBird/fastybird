<?php declare(strict_types = 1);

/**
 * ActionEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Events
 * @since          0.1.0
 *
 * @date           05.04.20
 */

namespace FastyBird\Automator\DevicesModule\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Automator\DevicesModule\Exceptions;
use Nette;
use function array_merge;

/**
 * Trigger condition entity listener
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConditionEntity implements Common\EventSubscriber
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
	 * @throws Exceptions\UniqueConditionConstraint
	 */
	public function onFlush(ORM\Event\OnFlushEventArgs $eventArgs): void
	{
		$manager = $eventArgs->getObjectManager();
		$uow = $manager->getUnitOfWork();

		// Check all scheduled updates
		foreach (array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates()) as $object) {
			if ($object instanceof Entities\Conditions\PropertyCondition) {
				$trigger = $object->getTrigger();

				foreach ($trigger->getConditions() as $condition) {
					if (!$condition->getId()->equals($object->getId())) {
						if (
							$condition instanceof Entities\Conditions\DevicePropertyCondition
							&& $object instanceof Entities\Conditions\DevicePropertyCondition
						) {
							if (
								$condition->getDevice()->equals($object->getDevice())
								&& $condition->getProperty()->equals($object->getProperty())
							) {
								throw new Exceptions\UniqueConditionConstraint(
									'Not same property in trigger conditions',
								);
							}
						} elseif (
							$condition instanceof Entities\Conditions\ChannelPropertyCondition
							&& $object instanceof Entities\Conditions\ChannelPropertyCondition
						) {
							if (
								$condition->getDevice()->equals($object->getDevice())
								&& $condition->getChannel()->equals($object->getChannel())
								&& $condition->getProperty()->equals($object->getProperty())
							) {
								throw new Exceptions\UniqueConditionConstraint(
									'Not same property in trigger conditions',
								);
							}
						}
					}
				}
			}
		}
	}

}
