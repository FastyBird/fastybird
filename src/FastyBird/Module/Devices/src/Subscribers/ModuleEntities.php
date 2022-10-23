<?php declare(strict_types = 1);

/**
 * ModuleEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.03.20
 */

namespace FastyBird\Module\Devices\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Exception;
use FastyBird\Module\Devices\DataStorage;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use League\Flysystem;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use ReflectionClass;
use function count;
use function str_starts_with;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ModuleEntities implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly ORM\EntityManagerInterface $entityManager,
		private readonly Models\States\DevicePropertiesRepository $devicePropertiesStatesRepository,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesRepository $channelPropertiesStatesRepository,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Models\States\ConnectorPropertiesRepository $connectorPropertiesStatesRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly DataStorage\Writer $configurationDataWriter,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
			ORM\Events::postRemove,
		];
	}

	/**
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidState
	 * @throws Flysystem\FilesystemException
	 */
	public function postPersist(ORM\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->dispatcher?->dispatch(new Events\EntityCreated($entity));

		$this->configurationDataWriter->write();
	}

	/**
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidState
	 * @throws Flysystem\FilesystemException
	 */
	public function postUpdate(ORM\Event\LifecycleEventArgs $eventArgs): void
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
			!$entity instanceof Entities\Entity
			|| !$this->validateNamespace($entity)
			|| $uow->isScheduledForDelete($entity)
		) {
			return;
		}

		$this->dispatcher?->dispatch(new Events\EntityUpdated($entity));

		$this->configurationDataWriter->write();
	}

	/**
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidState
	 * @throws Flysystem\FilesystemException
	 */
	public function postRemove(ORM\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->dispatcher?->dispatch(new Events\EntityDeleted($entity));

		// Property states cleanup
		if ($entity instanceof Entities\Connectors\Properties\Dynamic) {
			try {
				$state = $this->connectorPropertiesStatesRepository->findOne($entity);

				if ($state !== null) {
					$this->connectorPropertiesStatesManager->delete($entity, $state);
				}
			} catch (Exceptions\NotImplemented) {
				return;
			}
		} elseif ($entity instanceof Entities\Devices\Properties\Dynamic) {
			try {
				$state = $this->devicePropertiesStatesRepository->findOne($entity);

				if ($state !== null) {
					$this->devicePropertiesStatesManager->delete($entity, $state);
				}
			} catch (Exceptions\NotImplemented) {
				return;
			}
		} elseif ($entity instanceof Entities\Channels\Properties\Dynamic) {
			try {
				$state = $this->channelPropertiesStatesRepository->findOne($entity);

				if ($state !== null) {
					$this->channelPropertiesStatesManager->delete($entity, $state);
				}
			} catch (Exceptions\NotImplemented) {
				return;
			}
		}

		$this->configurationDataWriter->write();
	}

	private function validateNamespace(object $entity): bool
	{
		$rc = new ReflectionClass($entity);

		if (str_starts_with($rc->getNamespaceName(), 'FastyBird\Module\Devices')) {
			return true;
		}

		foreach ($rc->getInterfaces() as $interface) {
			if (str_starts_with($interface->getNamespaceName(), 'FastyBird\Module\Devices')) {
				return true;
			}
		}

		return false;
	}

}
