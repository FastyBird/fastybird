<?php declare(strict_types = 1);

/**
 * EntitiesSubscriber.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           28.08.20
 */

namespace FastyBird\Module\Triggers\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use Nette;
use Nette\Utils;
use ReflectionClass;
use ReflectionException;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EntitiesSubscriber implements Common\EventSubscriber
{

	private const ACTION_CREATED = 'created';
	private const ACTION_UPDATED = 'updated';
	private const ACTION_DELETED = 'deleted';

	use Nette\SmartObject;

	/** @var Models\States\ActionsRepository */
	private Models\States\ActionsRepository $actionStateRepository;

	/** @var Models\States\ConditionsRepository */
	private Models\States\ConditionsRepository $conditionStateRepository;

	/** @var ExchangePublisher\Publisher|null */
	private ?ExchangePublisher\Publisher $publisher;

	/** @var ExchangeEntities\EntityFactory */
	private ExchangeEntities\EntityFactory $entityFactory;

	/** @var ORM\EntityManagerInterface */
	private ORM\EntityManagerInterface $entityManager;

	public function __construct(
		Models\States\ActionsRepository $actionStateRepository,
		Models\States\ConditionsRepository $conditionStateRepository,
		ExchangeEntities\EntityFactory $entityFactory,
		ORM\EntityManagerInterface $entityManager,
		?ExchangePublisher\Publisher $publisher = null
	) {
		$this->actionStateRepository = $actionStateRepository;
		$this->conditionStateRepository = $conditionStateRepository;
		$this->entityFactory = $entityFactory;
		$this->publisher = $publisher;
		$this->entityManager = $entityManager;
	}

	/**
	 * Register events
	 *
	 * @return string[]
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::onFlush,
			ORM\Events::prePersist,
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
		];
	}

	/**
	 * @return void
	 */
	public function onFlush(): void
	{
		$uow = $this->entityManager->getUnitOfWork();

		$processedEntities = [];

		$processEntities = [];

		foreach ($uow->getScheduledEntityDeletions() as $entity) {
			// Check for valid entity
			if (!$entity instanceof Entities\IEntity || !$this->validateNamespace($entity)) {
				continue;
			}

			// Doctrine is fine deleting elements multiple times. We are not.
			$hash = $this->getHash($entity, $uow->getEntityIdentifier($entity));

			if (in_array($hash, $processedEntities, true)) {
				continue;
			}

			$processedEntities[] = $hash;

			$processEntities[] = $entity;
		}

		foreach ($processEntities as $entity) {
			$this->publishEntity($entity, self::ACTION_DELETED);
		}
	}

	/**
	 * @param object $entity
	 *
	 * @return bool
	 */
	private function validateNamespace(object $entity): bool
	{
		try {
			$rc = new ReflectionClass($entity);

		} catch (ReflectionException $ex) {
			return false;
		}

		return str_starts_with($rc->getNamespaceName(), 'FastyBird\Module\Triggers');
	}

	/**
	 * @param Entities\IEntity $entity
	 * @param mixed[] $identifier
	 *
	 * @return string
	 */
	private function getHash(Entities\IEntity $entity, array $identifier): string
	{
		return implode(
			' ',
			array_merge(
				[$this->getRealClass(get_class($entity))],
				$identifier
			)
		);
	}

	/**
	 * @param string $class
	 *
	 * @return string
	 */
	private function getRealClass(string $class): string
	{
		$pos = strrpos($class, '\\' . Persistence\Proxy::MARKER . '\\');

		if ($pos === false) {
			return $class;
		}

		return substr($class, $pos + Persistence\Proxy::MARKER_LENGTH + 2);
	}

	/**
	 * @param Entities\IEntity $entity
	 * @param string $action
	 *
	 * @return void
	 */
	private function publishEntity(Entities\IEntity $entity, string $action): void
	{
		if ($this->publisher === null) {
			return;
		}

		if (!method_exists($entity, 'toArray')) {
			return;
		}

		$publishRoutingKey = null;

		switch ($action) {
			case self::ACTION_CREATED:
				foreach (TriggersModule\Constants::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if ($this->validateEntity($entity, $class)) {
						$publishRoutingKey = MetadataTypes\RoutingKeyType::get($routingKey);
					}
				}

				break;

			case self::ACTION_UPDATED:
				foreach (TriggersModule\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if ($this->validateEntity($entity, $class)) {
						$publishRoutingKey = MetadataTypes\RoutingKeyType::get($routingKey);
					}
				}

				break;

			case self::ACTION_DELETED:
				foreach (TriggersModule\Constants::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if ($this->validateEntity($entity, $class)) {
						$publishRoutingKey = MetadataTypes\RoutingKeyType::get($routingKey);
					}
				}

				break;
		}

		if ($publishRoutingKey !== null) {
			if ($entity instanceof Entities\Actions\IAction) {
				try {
					$state = $this->actionStateRepository->findOne($entity);

				} catch (Exceptions\NotImplementedException $ex) {
					$this->publisher->publish(
						$entity->getSource(),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey)
					);

					return;
				}

				$this->publisher->publish(
					$entity->getSource(),
					$publishRoutingKey,
					$this->entityFactory->create(Utils\Json::encode(array_merge($state !== null ? [
						'is_triggered' => $state->isTriggered(),
					] : [], $entity->toArray())), $publishRoutingKey)
				);

			} elseif ($entity instanceof Entities\Conditions\ICondition) {
				try {
					$state = $this->conditionStateRepository->findOne($entity);

				} catch (Exceptions\NotImplementedException $ex) {
					$this->publisher->publish(
						$entity->getSource(),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey)
					);

					return;
				}

				$this->publisher->publish(
					$entity->getSource(),
					$publishRoutingKey,
					$this->entityFactory->create(Utils\Json::encode(array_merge($state !== null ? [
						'is_fulfilled' => $state->isFulfilled(),
					] : [], $entity->toArray())), $publishRoutingKey)
				);

			} elseif ($entity instanceof Entities\Triggers\ITrigger) {
				try {
					if (count($entity->getActions()) > 0) {
						$isTriggered = true;

						foreach ($entity->getActions() as $action) {
							$state = $this->actionStateRepository->findOne($action);

							if ($state === null || $state->isTriggered() === false) {
								$isTriggered = false;
							}
						}
					} else {
						$isTriggered = false;
					}
				} catch (Exceptions\NotImplementedException $ex) {
					$isTriggered = null;
				}

				if ($entity instanceof Entities\Triggers\IAutomaticTrigger) {
					try {
						if (count($entity->getActions()) > 0) {
							$isFulfilled = true;

							foreach ($entity->getConditions() as $condition) {
								$state = $this->conditionStateRepository->findOne($condition);

								if ($state === null || $state->isFulfilled() === false) {
									$isFulfilled = false;
								}
							}
						} else {
							$isFulfilled = false;
						}
					} catch (Exceptions\NotImplementedException $ex) {
						$isFulfilled = null;
					}

					$this->publisher->publish(
						$entity->getSource(),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode(array_merge([
							'is_triggered' => $isTriggered,
							'is_fulfilled' => $isFulfilled,
						], $entity->toArray())), $publishRoutingKey)
					);

				} else {
					$this->publisher->publish(
						$entity->getSource(),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode(array_merge([
							'is_triggered' => $isTriggered,
						], $entity->toArray())), $publishRoutingKey)
					);
				}
			} else {
				$this->publisher->publish(
					$entity->getSource(),
					$publishRoutingKey,
					$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey)
				);
			}
		}
	}

	/**
	 * @param Entities\IEntity $entity
	 * @param string $class
	 *
	 * @return bool
	 */
	private function validateEntity(Entities\IEntity $entity, string $class): bool
	{
		$result = false;

		if (get_class($entity) === $class) {
			$result = true;
		}

		if (is_subclass_of($entity, $class)) {
			$result = true;
		}

		return $result;
	}

	/**
	 * @param ORM\Event\LifecycleEventArgs $eventArgs
	 *
	 * @return void
	 */
	public function prePersist(ORM\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\IEntity || !$this->validateNamespace($entity)) {
			return;
		}

		if ($entity instanceof Entities\Triggers\IManualTrigger) {
			new Entities\Triggers\Controls\Control(
				MetadataTypes\ControlNameType::NAME_TRIGGER,
				$entity,
			);
		}
	}

	/**
	 * @param ORM\Event\LifecycleEventArgs $eventArgs
	 *
	 * @return void
	 */
	public function postPersist(ORM\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\IEntity || !$this->validateNamespace($entity)) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_CREATED);
	}

	/**
	 * @param ORM\Event\LifecycleEventArgs $eventArgs
	 *
	 * @return void
	 */
	public function postUpdate(ORM\Event\LifecycleEventArgs $eventArgs): void
	{
		$uow = $this->entityManager->getUnitOfWork();

		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Get changes => should be already computed here (is a listener)
		$changeset = $uow->getEntityChangeSet($entity);

		// If we have no changes left => don't create revision log
		if (count($changeset) === 0) {
			return;
		}

		// Check for valid entity
		if (
			!$entity instanceof Entities\IEntity
			|| !$this->validateNamespace($entity)
			|| $uow->isScheduledForDelete($entity)
		) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_UPDATED);
	}

}
