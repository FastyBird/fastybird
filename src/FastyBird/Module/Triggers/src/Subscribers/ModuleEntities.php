<?php declare(strict_types = 1);

/**
 * ModuleEntities.php
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
use Exception;
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use IPub\Phone\Exceptions as PhoneExceptions;
use Nette;
use Nette\Utils;
use ReflectionClass;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function is_a;
use function str_starts_with;
use function strrpos;
use function substr;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:TriggersModule!
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
		private readonly Models\States\ActionsRepository $actionStateRepository,
		private readonly Models\States\ConditionsRepository $conditionStateRepository,
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly ORM\EntityManagerInterface $entityManager,
		private readonly ExchangePublisher\Publisher $publisher,
	)
	{
	}

	/**
	 * Register events
	 *
	 * @return Array<string>
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
	 * @throws Exception
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
	public function onFlush(): void
	{
		$uow = $this->entityManager->getUnitOfWork();

		$processedEntities = [];

		$processEntities = [];

		foreach ($uow->getScheduledEntityDeletions() as $entity) {
			// Check for valid entity
			if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
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

	public function prePersist(ORM\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Entity || !$this->validateNamespace($entity)) {
			return;
		}

		if ($entity instanceof Entities\Triggers\ManualTrigger) {
			new Entities\Triggers\Controls\Control(
				MetadataTypes\ControlName::NAME_TRIGGER,
				$entity,
			);
		}
	}

	/**
	 * @throws Exception
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
	public function postPersist(ORM\Event\LifecycleEventArgs $eventArgs): void
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
	 * @throws Exception
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
			!$entity instanceof Entities\Entity
			|| !$this->validateNamespace($entity)
			|| $uow->isScheduledForDelete($entity)
		) {
			return;
		}

		$this->publishEntity($entity, self::ACTION_UPDATED);
	}

	/**
	 * @throws Exception
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
	private function publishEntity(Entities\Entity $entity, string $action): void
	{
		$publishRoutingKey = null;

		switch ($action) {
			case self::ACTION_CREATED:
				foreach (Triggers\Constants::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = MetadataTypes\RoutingKey::get($routingKey);
					}
				}

				break;
			case self::ACTION_UPDATED:
				foreach (Triggers\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = MetadataTypes\RoutingKey::get($routingKey);
					}
				}

				break;
			case self::ACTION_DELETED:
				foreach (Triggers\Constants::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING as $class => $routingKey) {
					if (is_a($entity, $class)) {
						$publishRoutingKey = MetadataTypes\RoutingKey::get($routingKey);
					}
				}

				break;
		}

		if ($publishRoutingKey !== null) {
			if ($entity instanceof Entities\Actions\Action) {
				try {
					$state = $this->actionStateRepository->findOne($entity);

				} catch (Exceptions\NotImplemented) {
					$this->publisher->publish(
						$entity->getSource(),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey),
					);

					return;
				}

				$this->publisher->publish(
					$entity->getSource(),
					$publishRoutingKey,
					$this->entityFactory->create(Utils\Json::encode(array_merge($state !== null ? [
						'is_triggered' => $state->isTriggered(),
					] : [], $entity->toArray())), $publishRoutingKey),
				);

			} elseif ($entity instanceof Entities\Conditions\Condition) {
				try {
					$state = $this->conditionStateRepository->findOne($entity);

				} catch (Exceptions\NotImplemented) {
					$this->publisher->publish(
						$entity->getSource(),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey),
					);

					return;
				}

				$this->publisher->publish(
					$entity->getSource(),
					$publishRoutingKey,
					$this->entityFactory->create(Utils\Json::encode(array_merge($state !== null ? [
						'is_fulfilled' => $state->isFulfilled(),
					] : [], $entity->toArray())), $publishRoutingKey),
				);

			} elseif ($entity instanceof Entities\Triggers\Trigger) {
				try {
					if (count($entity->getActions()) > 0) {
						$isTriggered = true;

						foreach ($entity->getActions() as $item) {
							$state = $this->actionStateRepository->findOne($item);

							if ($state === null || $state->isTriggered() === false) {
								$isTriggered = false;
							}
						}
					} else {
						$isTriggered = false;
					}
				} catch (Exceptions\NotImplemented) {
					$isTriggered = null;
				}

				if ($entity instanceof Entities\Triggers\AutomaticTrigger) {
					try {
						if (count($entity->getActions()) > 0) {
							$isFulfilled = true;

							foreach ($entity->getConditions() as $item) {
								$state = $this->conditionStateRepository->findOne($item);

								if ($state === null || $state->isFulfilled() === false) {
									$isFulfilled = false;
								}
							}
						} else {
							$isFulfilled = false;
						}
					} catch (Exceptions\NotImplemented) {
						$isFulfilled = null;
					}

					$this->publisher->publish(
						$entity->getSource(),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode(array_merge([
							'is_triggered' => $isTriggered,
							'is_fulfilled' => $isFulfilled,
						], $entity->toArray())), $publishRoutingKey),
					);

				} else {
					$this->publisher->publish(
						$entity->getSource(),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode(array_merge([
							'is_triggered' => $isTriggered,
						], $entity->toArray())), $publishRoutingKey),
					);
				}
			} else {
				$this->publisher->publish(
					$entity->getSource(),
					$publishRoutingKey,
					$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey),
				);
			}
		}
	}

	/**
	 * @param Array<mixed> $identifier
	 */
	private function getHash(Entities\Entity $entity, array $identifier): string
	{
		return implode(
			' ',
			array_merge(
				[$this->getRealClass($entity::class)],
				$identifier,
			),
		);
	}

	private function getRealClass(string $class): string
	{
		$pos = strrpos($class, '\\' . Persistence\Proxy::MARKER . '\\');

		if ($pos === false) {
			return $class;
		}

		return substr($class, $pos + Persistence\Proxy::MARKER_LENGTH + 2);
	}

	private function validateNamespace(object $entity): bool
	{
		$rc = new ReflectionClass($entity);

		if (str_starts_with($rc->getNamespaceName(), 'FastyBird\Module\Triggers')) {
			return true;
		}

		foreach ($rc->getInterfaces() as $interface) {
			if (str_starts_with($interface->getNamespaceName(), 'FastyBird\Module\Triggers')) {
				return true;
			}
		}

		return false;
	}

}
