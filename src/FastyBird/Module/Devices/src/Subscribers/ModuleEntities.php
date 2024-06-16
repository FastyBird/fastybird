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
use FastyBird\Library\Application\Events as ApplicationEvents;
use FastyBird\Library\Exchange\Documents as ExchangeDocuments;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Types;
use Nette;
use Nette\Caching;
use Nette\Utils;
use ReflectionClass;
use function count;
use function in_array;
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

	private bool $useAsync = false;

	public function __construct(
		private readonly ORM\EntityManagerInterface $entityManager,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\Async\ConnectorPropertiesManager $asyncConnectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\Async\DevicePropertiesManager $asyncDevicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Models\States\Async\ChannelPropertiesManager $asyncChannelPropertiesStatesManager,
		private readonly ExchangeDocuments\DocumentFactory $documentFactory,
		private readonly ExchangePublisher\Publisher $publisher,
		private readonly ExchangePublisher\Async\Publisher $asyncPublisher,
		private readonly Caching\Cache $configurationBuilderCache,
		private readonly Caching\Cache $configurationRepositoryCache,
		private readonly Caching\Cache $stateCache,
		private readonly Caching\Cache $stateStorageCache,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			0 => ORM\Events::postPersist,
			1 => ORM\Events::postUpdate,
			2 => ORM\Events::preRemove,
			3 => ORM\Events::postRemove,

			ApplicationEvents\EventLoopStarted::class => 'enableAsync',
			ApplicationEvents\EventLoopStopped::class => 'disableAsync',
			ApplicationEvents\EventLoopStopping::class => 'disableAsync',
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws MetadataExceptions\Mapping
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->cleanCache($entity, self::ACTION_CREATED);

		$this->publishEntity($entity, self::ACTION_CREATED);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws MetadataExceptions\Mapping
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

		$this->cleanCache($entity, self::ACTION_UPDATED);

		$this->publishEntity($entity, self::ACTION_UPDATED);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws Exceptions\InvalidState
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
			if ($this->useAsync) {
				$this->asyncConnectorPropertiesStatesManager->delete($entity->getId());
			} else {
				$this->connectorPropertiesStatesManager->delete($entity->getId());
			}
		} elseif ($entity instanceof Entities\Devices\Properties\Dynamic) {
			if ($this->useAsync) {
				$this->asyncDevicePropertiesStatesManager->delete($entity->getId());
			} else {
				$this->devicePropertiesStatesManager->delete($entity->getId());
			}
		} elseif ($entity instanceof Entities\Channels\Properties\Dynamic) {
			if ($this->useAsync) {
				$this->asyncChannelPropertiesStatesManager->delete($entity->getId());
			} else {
				$this->channelPropertiesStatesManager->delete($entity->getId());
			}
		}
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws MetadataExceptions\Mapping
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

		$this->cleanCache($entity, self::ACTION_DELETED);
	}

	public function enableAsync(): void
	{
		$this->useAsync = true;
	}

	public function disableAsync(): void
	{
		$this->useAsync = false;
	}

	private function cleanCache(Entities\Entity $entity, string $action): void
	{
		if ($entity instanceof Entities\Connectors\Connector) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CONNECTORS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Connectors\Properties\Property) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CONNECTORS_PROPERTIES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS_PROPERTIES->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Connectors\Controls\Control) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CONNECTORS_CONTROLS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CONNECTORS_CONTROLS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Devices\Device) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DEVICES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::DEVICES->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Devices\Properties\Property) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DEVICES_PROPERTIES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::DEVICES_PROPERTIES->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Devices\Controls\Control) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DEVICES_CONTROLS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::DEVICES_CONTROLS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Channels\Channel) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CHANNELS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Channels\Properties\Property) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CHANNELS_PROPERTIES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS_PROPERTIES->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Channels\Controls\Control) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::CHANNELS_CONTROLS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::CHANNELS_CONTROLS->value,
					$entity->getId()->toString(),
				],
			]);
		}

		if (in_array($action, [self::ACTION_UPDATED, self::ACTION_DELETED], true)) {
			$this->stateCache->clean([
				Caching\Cache::Tags => [$entity->getId()->toString()],
			]);
			$this->stateStorageCache->clean([
				Caching\Cache::Tags => [$entity->getId()->toString()],
			]);
		}
	}

	/**
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws MetadataExceptions\Mapping
	 */
	private function publishEntity(Entities\Entity $entity, string $action): void
	{
		$publishRoutingKey = null;

		switch ($action) {
			case self::ACTION_CREATED:
				foreach (Devices\Constants::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
			case self::ACTION_UPDATED:
				foreach (Devices\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
			case self::ACTION_DELETED:
				foreach (Devices\Constants::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
		}

		if ($publishRoutingKey !== null) {
			$this->getPublisher()->publish(
				MetadataTypes\Sources\Module::DEVICES,
				$publishRoutingKey,
				$this->documentFactory->create(
					Utils\ArrayHash::from($entity->toArray()),
					$publishRoutingKey,
				),
			);
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

	private function getPublisher(): ExchangePublisher\Publisher|ExchangePublisher\Async\Publisher
	{
		return $this->useAsync ? $this->asyncPublisher : $this->publisher;
	}

}
