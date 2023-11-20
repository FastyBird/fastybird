<?php declare(strict_types = 1);

/**
 * ModuleEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           22.03.20
 */

namespace FastyBird\Module\Devices\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Library\Exchange\Documents as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Utilities;
use Nette;
use Nette\Utils;
use ReflectionClass;
use function array_merge;
use function count;
use function is_a;
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

	private const ACTION_CREATED = 'created';

	private const ACTION_UPDATED = 'updated';

	private const ACTION_DELETED = 'deleted';

	/**
	 * @param Models\Configuration\Connectors\Properties\Repository<MetadataDocuments\DevicesModule\ConnectorDynamicProperty> $connectorsPropertiesConfigurationRepository
	 * @param Models\Configuration\Devices\Properties\Repository<MetadataDocuments\DevicesModule\DeviceDynamicProperty> $devicesPropertiesConfigurationRepository
	 * @param Models\Configuration\Channels\Properties\Repository<MetadataDocuments\DevicesModule\ChannelDynamicProperty> $channelsPropertiesConfigurationRepository
	 */
	public function __construct(
		private readonly ORM\EntityManagerInterface $entityManager,
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly Models\Configuration\Builder $configurationBuilder,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Utilities\ConnectorPropertiesStates $connectorPropertiesStates,
		private readonly Utilities\DevicePropertiesStates $devicePropertiesStates,
		private readonly Utilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly ExchangeEntities\DocumentFactory $entityFactory,
		private readonly ExchangePublisher\Publisher $publisher,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
			ORM\Events::preRemove,
			ORM\Events::postRemove,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->configurationBuilder->build();

		$this->publishEntity($entity, self::ACTION_CREATED);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
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
			!$entity instanceof Entities\Entity
			|| !$this->validateNamespace($entity)
			|| $uow->isScheduledForDelete($entity)
		) {
			return;
		}

		$this->configurationBuilder->build();

		$this->publishEntity($entity, self::ACTION_UPDATED);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function preRemove(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		// Property states cleanup
		if ($entity instanceof Entities\Connectors\Properties\Dynamic) {
			try {
				$findProperty = new Devices\Queries\Configuration\FindConnectorDynamicProperties();
				$findProperty->byId($entity->getId());

				$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
					$findProperty,
					MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
				);

				if ($property !== null) {
					$this->connectorPropertiesStatesManager->delete($property);
				}
			} catch (Exceptions\NotImplemented) {
				return;
			}
		} elseif ($entity instanceof Entities\Devices\Properties\Dynamic) {
			try {
				$findProperty = new Devices\Queries\Configuration\FindDeviceDynamicProperties();
				$findProperty->byId($entity->getId());

				$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
					$findProperty,
					MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
				);

				if ($property !== null) {
					$this->devicePropertiesStatesManager->delete($property);
				}
			} catch (Exceptions\NotImplemented) {
				return;
			}
		} elseif ($entity instanceof Entities\Channels\Properties\Dynamic) {
			try {
				$findProperty = new Devices\Queries\Configuration\FindChannelDynamicProperties();
				$findProperty->byId($entity->getId());

				$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
					$findProperty,
					MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
				);

				if ($property !== null) {
					$this->channelPropertiesStatesManager->delete($property);
				}
			} catch (Exceptions\NotImplemented) {
				return;
			}
		}
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function postRemove(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->configurationBuilder->build();

		$this->publishEntity($entity, self::ACTION_DELETED);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws Utils\JsonException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function publishEntity(Entities\Entity $entity, string $action): void
	{
		$publishRoutingKey = null;

		switch ($action) {
			case self::ACTION_CREATED:
				foreach (Devices\Constants::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = MetadataTypes\RoutingKey::get($routingKey);
					}
				}

				break;
			case self::ACTION_UPDATED:
				foreach (Devices\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = MetadataTypes\RoutingKey::get($routingKey);
					}
				}

				break;
			case self::ACTION_DELETED:
				foreach (Devices\Constants::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = MetadataTypes\RoutingKey::get($routingKey);
					}
				}

				break;
		}

		if ($publishRoutingKey !== null) {
			if ($entity instanceof Entities\Devices\Properties\Dynamic) {
				try {
					$state = $action === self::ACTION_UPDATED ? $this->devicePropertiesStates->readValue(
						$entity,
					) : null;

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$entity->toArray(),
									$state?->toArray() ?? [],
								),
							),
							$publishRoutingKey,
						),
					);

				} catch (Exceptions\NotImplemented) {
					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								$entity->toArray(),
							),
							$publishRoutingKey,
						),
					);
				}
			} elseif ($entity instanceof Entities\Channels\Properties\Dynamic) {
				try {
					$state = $action === self::ACTION_UPDATED
						? $this->channelPropertiesStates->readValue($entity)
						: null;

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$entity->toArray(),
									$state?->toArray() ?? [],
								),
							),
							$publishRoutingKey,
						),
					);

				} catch (Exceptions\NotImplemented) {
					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								$entity->toArray(),
							),
							$publishRoutingKey,
						),
					);
				}
			} elseif ($entity instanceof Entities\Connectors\Properties\Dynamic) {
				try {
					$state = $action === self::ACTION_UPDATED
						? $this->connectorPropertiesStates->readValue($entity)
						: null;

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$entity->toArray(),
									$state?->toArray() ?? [],
								),
							),
							$publishRoutingKey,
						),
					);

				} catch (Exceptions\NotImplemented) {
					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								$entity->toArray(),
							),
							$publishRoutingKey,
						),
					);
				}
			} else {
				$this->publisher->publish(
					MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
					$publishRoutingKey,
					$this->entityFactory->create(
						Utils\Json::encode(
							$entity->toArray(),
						),
						$publishRoutingKey,
					),
				);
			}
		}
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
