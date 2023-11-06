<?php declare(strict_types = 1);

/**
 * VariablePropertiesEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           22.10.23
 */

namespace FastyBird\Module\Devices\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use function count;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class VariablePropertiesEntities implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly ORM\EntityManagerInterface $entityManager,
		private readonly Models\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly Models\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postUpdate,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function postUpdate(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$uow = $this->entityManager->getUnitOfWork();

		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Get changes => should be already computed here (is a listener)
		$changeSet = $uow->getEntityChangeSet($entity);

		// If we have no changes left => don't create revision log
		if (count($changeSet) === 0) {
			return;
		}

		// Check for valid entity
		if (
			$entity instanceof Entities\Devices\Properties\Mapped
			&& $entity->getParent() instanceof Entities\Devices\Properties\Variable
		) {
			$this->devicesPropertiesManager->update(
				$entity->getParent(),
				Utils\ArrayHash::from([
					'value' => $entity->getValue(),
				]),
			);

		} elseif (
			$entity instanceof Entities\Channels\Properties\Mapped
			&& $entity->getParent() instanceof Entities\Channels\Properties\Variable
		) {
			$this->channelsPropertiesManager->update(
				$entity->getParent(),
				Utils\ArrayHash::from([
					'value' => $entity->getValue(),
				]),
			);
		}
	}

}
