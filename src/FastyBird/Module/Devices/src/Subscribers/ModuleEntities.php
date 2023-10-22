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
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Utilities;
use IPub\Phone\Exceptions as PhoneExceptions;
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

	public function __construct(
		private readonly ORM\EntityManagerInterface $entityManager,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Utilities\ConnectorPropertiesStates $connectorPropertiesStates,
		private readonly Utilities\DevicePropertiesStates $devicePropertiesStates,
		private readonly Utilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly ExchangePublisher\Publisher $publisher,
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
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_CREATED);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws ExchangeExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
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

		$this->publishEntity($entity, self::ACTION_UPDATED);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws ExchangeExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Utils\JsonException
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function postRemove(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_DELETED);

		// Property states cleanup
		if ($entity instanceof Entities\Connectors\Properties\Dynamic) {
			try {
				$this->connectorPropertiesStatesManager->delete($entity);
			} catch (Exceptions\NotImplemented) {
				return;
			}
		} elseif ($entity instanceof Entities\Devices\Properties\Dynamic) {
			try {
				$this->devicePropertiesStatesManager->delete($entity);
			} catch (Exceptions\NotImplemented) {
				return;
			}
		} elseif ($entity instanceof Entities\Channels\Properties\Dynamic) {
			try {
				$this->channelPropertiesStatesManager->delete($entity);
			} catch (Exceptions\NotImplemented) {
				return;
			}
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidState
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws Utils\JsonException
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
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
					$state = $this->devicePropertiesStates->readValue($entity);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$state?->toArray() ?? [],
									$entity->toArray(),
								),
							),
							$publishRoutingKey,
						),
					);

				} catch (Exceptions\NotImplemented) {
					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey),
					);
				}
			} elseif ($entity instanceof Entities\Channels\Properties\Dynamic) {
				try {
					$state = $this->channelPropertiesStates->readValue($entity);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$state?->toArray() ?? [],
									$entity->toArray(),
								),
							),
							$publishRoutingKey,
						),
					);

				} catch (Exceptions\NotImplemented) {
					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey),
					);
				}
			} elseif ($entity instanceof Entities\Connectors\Properties\Dynamic) {
				try {
					$state = $this->connectorPropertiesStates->readValue($entity);

					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(
							Utils\Json::encode(
								array_merge(
									$state?->toArray() ?? [],
									$entity->toArray(),
								),
							),
							$publishRoutingKey,
						),
					);

				} catch (Exceptions\NotImplemented) {
					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey),
					);
				}
			} else {
				$this->publisher->publish(
					MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
					$publishRoutingKey,
					$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey),
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
