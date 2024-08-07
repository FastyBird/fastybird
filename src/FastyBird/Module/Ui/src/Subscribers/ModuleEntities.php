<?php declare(strict_types = 1);

/**
 * ModuleEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UiModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           06.08.24
 */

namespace FastyBird\Module\Ui\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Library\Application\Events as ApplicationEvents;
use FastyBird\Library\Exchange\Documents as ExchangeDocuments;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Types;
use Nette;
use Nette\Caching;
use Nette\Utils;
use ReflectionClass;
use function count;
use function is_a;
use function str_starts_with;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:UiModule!
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
		private readonly ExchangeDocuments\DocumentFactory $documentFactory,
		private readonly ExchangePublisher\Publisher $publisher,
		private readonly ExchangePublisher\Async\Publisher $asyncPublisher,
		private readonly Caching\Cache $configurationBuilderCache,
		private readonly Caching\Cache $configurationRepositoryCache,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			0 => ORM\Events::postPersist,
			1 => ORM\Events::postUpdate,
			2 => ORM\Events::postRemove,

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
		if ($entity instanceof Entities\Dashboards\Dashboard) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DASHBOARDS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::DASHBOARDS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Dashboards\Tabs\Tab) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::DASHBOARDS_TABS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::DASHBOARDS_TABS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Groups\Group) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::GROUPS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::GROUPS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Widgets\Widget) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::WIDGETS->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::WIDGETS->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Widgets\DataSources\DataSource) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::WIDGETS_DATA_SOURCES->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::WIDGETS_DATA_SOURCES->value,
					$entity->getId()->toString(),
				],
			]);
		} elseif ($entity instanceof Entities\Widgets\Displays\Display) {
			$this->configurationBuilderCache->clean([
				Caching\Cache::Tags => [Types\ConfigurationType::WIDGETS_DISPLAY->value],
			]);

			$this->configurationRepositoryCache->clean([
				Caching\Cache::Tags => [
					Types\ConfigurationType::WIDGETS_DISPLAY->value,
					$entity->getId()->toString(),
				],
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
				foreach (Ui\Constants::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
			case self::ACTION_UPDATED:
				foreach (Ui\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = $routingKey;

						break;
					}
				}

				break;
			case self::ACTION_DELETED:
				foreach (Ui\Constants::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
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

		if (str_starts_with($rc->getNamespaceName(), 'FastyBird\Module\Ui')) {
			return true;
		}

		foreach ($rc->getInterfaces() as $interface) {
			if (str_starts_with($interface->getNamespaceName(), 'FastyBird\Module\Ui')) {
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
