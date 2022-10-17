<?php declare(strict_types = 1);

/**
 * DynamicProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          0.65.0
 *
 * @date           25.06.22
 */

namespace FastyBird\DevicesModule\Subscribers;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Events;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\States;
use FastyBird\Exchange\Entities as ExchangeEntities;
use FastyBird\Exchange\Publisher as ExchangePublisher;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Metadata\Types as MetadataTypes;
use Nette;
use Nette\Utils;
use Symfony\Component\EventDispatcher;
use Throwable;
use function array_merge;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DynamicProperties implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\DataStorage\ConnectorPropertiesRepository $connectorPropertiesRepository,
		private readonly Models\DataStorage\DevicePropertiesRepository $devicePropertiesRepository,
		private readonly Models\DataStorage\ChannelPropertiesRepository $channelPropertiesRepository,
		private readonly ExchangeEntities\EntityFactory $entityFactory,
		private readonly ExchangePublisher\Container|null $publisher = null,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\StateEntityCreated::class => 'stateCreated',
			Events\StateEntityUpdated::class => 'stateUpdated',
			Events\StateEntityDeleted::class => 'stateDeleted',
		];
	}

	/**
	 * @throws MetadataExceptions\FileNotFound
	 * @throws Utils\JsonException
	 * @throws Throwable
	 */
	public function stateCreated(Events\StateEntityCreated $event): void
	{
		$this->refreshRepository($event->getProperty());

		$this->publishEntity($event->getProperty(), $event->getState());

		$parent = $this->findParent($event->getProperty());

		if ($parent !== null) {
			$this->refreshRepository($parent);

			$this->publishEntity($parent, $event->getState());
		}
	}

	/**
	 * @throws MetadataExceptions\FileNotFound
	 * @throws Utils\JsonException
	 * @throws Throwable
	 */
	public function stateUpdated(Events\StateEntityUpdated $event): void
	{
		if ($event->getPreviousState()->toArray() !== $event->getState()->toArray()) {
			$this->refreshRepository($event->getProperty());
		}

		$this->publishEntity($event->getProperty(), $event->getState());

		$parent = $this->findParent($event->getProperty());

		if ($parent !== null) {
			if ($event->getPreviousState()->toArray() !== $event->getState()->toArray()) {
				$this->refreshRepository($parent);
			}

			$this->publishEntity($parent, $event->getState());
		}
	}

	/**
	 * @throws MetadataExceptions\FileNotFound
	 * @throws Utils\JsonException
	 * @throws Throwable
	 */
	public function stateDeleted(Events\StateEntityDeleted $event): void
	{
		$this->refreshRepository($event->getProperty());

		$this->publishEntity($event->getProperty());

		$parent = $this->findParent($event->getProperty());

		if ($parent !== null) {
			$this->refreshRepository($parent);

			$this->publishEntity($parent);
		}
	}

	/**
	 * @throws MetadataExceptions\FileNotFound
	 * @throws Utils\JsonException
	 * @throws Throwable
	 */
	private function publishEntity(
		MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|Entities\Property $property,
		States\ConnectorProperty|States\ChannelProperty|States\DeviceProperty|null $state = null,
	): void
	{
		if ($this->publisher === null) {
			return;
		}

		if (
			$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ConnectorMappedProperty
			|| $property instanceof Entities\Connectors\Properties\Property
		) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED,
			);

		} elseif (
			$property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty
			|| $property instanceof Entities\Devices\Properties\Property
		) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED,
			);

		} elseif (
			$property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
			|| $property instanceof Entities\Channels\Properties\Property
		) {
			$routingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_REPORTED,
			);

		} else {
			return;
		}

		$this->publisher->publish(
			MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			$routingKey,
			$this->entityFactory->create(
				Utils\Json::encode(
					array_merge(
						$property->toArray(),
						$state?->toArray() ?? [],
					),
				),
				$routingKey,
			),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function findParent(
		MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|Entities\Property $property,
	): MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|Entities\Property|null
	{
		if (
			$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ConnectorMappedProperty
			|| $property instanceof Entities\Connectors\Properties\Dynamic
		) {
			return null;
		} elseif (
			$property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty
		) {
			if ($property->getParent() !== null) {
				return $this->devicePropertiesRepository->findById($property->getParent());
			}
		} elseif (
			$property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
		) {
			if ($property->getParent() !== null) {
				return $this->channelPropertiesRepository->findById($property->getParent());
			}
		} elseif (
			$property instanceof Entities\Devices\Properties\Dynamic
			|| $property instanceof Entities\Devices\Properties\Mapped
		) {
			return $property->getParent();
		} elseif (
			$property instanceof Entities\Channels\Properties\Dynamic
			|| $property instanceof Entities\Channels\Properties\Mapped
		) {
			return $property->getParent();
		}

		return null;
	}

	private function refreshRepository(
		MetadataEntities\DevicesModule\DynamicProperty|MetadataEntities\DevicesModule\VariableProperty|MetadataEntities\DevicesModule\MappedProperty|Entities\Property $property,
	): void
	{
		if (
			$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ConnectorMappedProperty
			|| $property instanceof Entities\Connectors\Properties\Dynamic
		) {
			$this->connectorPropertiesRepository->reset($property->getId());
		} elseif (
			$property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\DeviceMappedProperty
			|| $property instanceof Entities\Devices\Properties\Dynamic
			|| $property instanceof Entities\Devices\Properties\Mapped
		) {
			$this->devicePropertiesRepository->reset($property->getId());
		} elseif (
			$property instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
			|| $property instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
			|| $property instanceof Entities\Channels\Properties\Dynamic
			|| $property instanceof Entities\Channels\Properties\Mapped
		) {
			$this->channelPropertiesRepository->reset($property->getId());
		}
	}

}
