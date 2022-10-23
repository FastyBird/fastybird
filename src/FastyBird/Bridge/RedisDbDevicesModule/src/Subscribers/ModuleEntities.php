<?php declare(strict_types = 1);

/**
 * ModuleEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\Subscribers;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Publishers as RedisDbPublishers;
use IPub\Phone\Exceptions as PhoneExceptions;
use Nette;
use Nette\Utils;
use Symfony\Component\EventDispatcher;
use function array_merge;
use function is_a;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ModuleEntities implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	private const ACTION_CREATED = 'created';

	private const ACTION_UPDATED = 'updated';

	private const ACTION_DELETED = 'deleted';

	public function __construct(
		private readonly DevicesModels\States\DevicePropertiesRepository $devicePropertiesStatesRepository,
		private readonly DevicesModels\States\ChannelPropertiesRepository $channelPropertiesStatesRepository,
		private readonly DevicesModels\States\ConnectorPropertiesRepository $connectorPropertiesStatesRepository,
		private readonly MetadataEntities\RoutingFactory $entityFactory,
		private readonly RedisDbPublishers\Publisher $publisher,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DevicesEvents\EntityCreated::class => 'entityCreated',
			DevicesEvents\EntityUpdated::class => 'entityUpdated',
			DevicesEvents\EntityDeleted::class => 'entityDeleted',
		];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
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
	public function entityCreated(DevicesEvents\EntityCreated $event): void
	{
		$this->publishEntity($event->getEntity(), self::ACTION_CREATED);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
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
	public function entityUpdated(DevicesEvents\EntityUpdated $event): void
	{
		$this->publishEntity($event->getEntity(), self::ACTION_UPDATED);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
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
	public function entityDeleted(DevicesEvents\EntityDeleted $event): void
	{
		$this->publishEntity($event->getEntity(), self::ACTION_DELETED);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
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
	private function publishEntity(DevicesEntities\Entity $entity, string $action): void
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
			if ($entity instanceof DevicesEntities\Devices\Properties\Dynamic) {
				try {
					$state = $this->devicePropertiesStatesRepository->findOne($entity);

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

				} catch (DevicesExceptions\NotImplemented) {
					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey),
					);
				}
			} elseif ($entity instanceof DevicesEntities\Channels\Properties\Dynamic) {
				try {
					$state = $this->channelPropertiesStatesRepository->findOne($entity);

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

				} catch (DevicesExceptions\NotImplemented) {
					$this->publisher->publish(
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
						$publishRoutingKey,
						$this->entityFactory->create(Utils\Json::encode($entity->toArray()), $publishRoutingKey),
					);
				}
			} elseif ($entity instanceof DevicesEntities\Connectors\Properties\Dynamic) {
				try {
					$state = $this->connectorPropertiesStatesRepository->findOne($entity);

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

				} catch (DevicesExceptions\NotImplemented) {
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

}
